/********************************************************************************/
/*!
	@file			mmc_stm32f4.h
	@author         Nemui Trinomius (http://nemuisan.blog.bai.ne.jp)
    @version        4.00
    @date           2012.08.31
	@brief          MMC Driver For STM32 F-4  Devices					@n
					Based on STM32F2xx_StdPeriph_Driver V1.0.0.			@n
					Based on ChaN's FatFs Sample thanks!

    @section HISTORY
		2011.10.21	V1.00 Start Here.
		2012.01.31	V2.00 Fixed Variable-Handling for DMA.
		2012.03.31  V3.00 Optimized SPI Handlings.
		2012.08.31  V4.00 Updated Support grater than 32GB Cards.

    @section LICENSE
		BSD License. See Copyright.txt
*/
/********************************************************************************/
#ifndef __MMC_STM32F4_H
#define __MMC_STM32F4_H

#ifdef __cplusplus
 extern "C" {
#endif

/* Generic Inclusion */
#include <inttypes.h>
#include <stdbool.h>
#include "stm32f4xx.h"
#include "stm32f4xx_gpio.h"
#include "stm32f4xx_rcc.h"
#include "stm32f4xx_spi.h"
#include "stm32f4xx_dma.h"
/* uC Related Inclusion */
#include "stm32f4xx.h"
#include "diskio.h"

/* Defines */
//#define USE_SPIMMC_DMA
/*#define USE_PWRCTRL*/

#define SPIMMC						SPI3
#define SPIMMC_PIN_CS				GPIO_Pin_2
#define SPIMMC_PIN_SCK				GPIO_Pin_10
#define SPIMMC_PIN_MISO    			GPIO_Pin_11
#define SPIMMC_PIN_MOSI				GPIO_Pin_12
#define SPIMMC_SRC_CS				GPIO_PinSource2
#define SPIMMC_SRC_SCK				GPIO_PinSource10
#define SPIMMC_SRC_MISO				GPIO_PinSource11
#define SPIMMC_SRC_MOSI				GPIO_PinSource12

#define SPIMMC_PORT_CS				GPIOD
#define SPIMMC_PORT_SCK				GPIOC
#define SPIMMC_PORT_MISO    		GPIOC
#define SPIMMC_PORT_MOSI			GPIOC

#define SPIMMC_CLK_CS				RCC_AHB1Periph_GPIOD
#define SPIMMC_CLK_SCK				RCC_AHB1Periph_GPIOC
#define SPIMMC_CLK_MISO    			RCC_AHB1Periph_GPIOC
#define SPIMMC_CLK_MOSI				RCC_AHB1Periph_GPIOC
 
/* STM32 SPI Peripheral Settings */
#define SPI_PERIF_CLK(x)        	RCC_APB2PeriphClockCmd(RCC_APB1Periph_SPI3,x)
#define SPI_GPIO_CLK(x)				RCC_AHB1PeriphClockCmd(SPIMMC_CLK_CS,x); 		\
									RCC_AHB1PeriphClockCmd(SPIMMC_CLK_SCK,x); 		\
									RCC_AHB1PeriphClockCmd(SPIMMC_CLK_MISO,x); 		\
									RCC_AHB1PeriphClockCmd(SPIMMC_CLK_MOSI,x)

#define SPI_AF_SET()				GPIO_PinAFConfig(SPIMMC_PORT_SCK,SPIMMC_SRC_SCK,GPIO_AF_SPI3);	 \
									GPIO_PinAFConfig(SPIMMC_PORT_MISO,SPIMMC_SRC_MISO,GPIO_AF_SPI3); \
									GPIO_PinAFConfig(SPIMMC_PORT_MOSI,SPIMMC_SRC_MOSI,GPIO_AF_SPI3); \





#define MMC_CS_PORT						GPIOD
#define MMC_CS_CLOCK					RCC_AHB1Periph_GPIOD
#define MMC_DATA_CLOCK					RCC_AHB1Periph_GPIOC
#define MMC_CS							GPIO_Pin_2

/* SPI SCK,MOSI,MISO Pis */
#define MMC_SPI_PORT					GPIOC
#define MMC_SPI							SPI3
#define MMC_SPI_CLOCK					RCC_APB1Periph_SPI3
#define MMC_AF							GPIO_AF_SPI3

#define MMC_SCK							GPIO_Pin_10
#define MMC_MOSI						GPIO_Pin_12
#define MMC_MISO						GPIO_Pin_11

#define MMC_SCK_AF						GPIO_PinSource10
#define MMC_MOSI_AF						GPIO_PinSource12
#define MMC_MISO_AF						GPIO_PinSource11












/* STM32 SPI DMA Settings */
#define SPIMMC_DMA_STREAM_CLOCK 	RCC_AHB1Periph_DMA2
#define SPIMMC_DMA_CHANNEL_RX		DMA_Channel_3
#define SPIMMC_DMA_CHANNEL_TX		DMA_Channel_3
#define SPIMMC_DMA_STREAM_RX     	DMA2_Stream2
#define SPIMMC_DMA_STREAM_TX     	DMA2_Stream3
#define SPIMMC_DMA_FLAG_RX_TC       DMA_FLAG_TCIF2
#define SPIMMC_DMA_FLAG_TX_TC       DMA_FLAG_TCIF3
#define DMA_STREAM_IRQ           	DMA2_Stream3_IRQn


/* Socket Contscts is not used */
#define SOCKPORT					1			/* Socket contact port 	*/
#define SOCKWP						1			/* Write protect switch	*/
#define SOCKINS						1			/* Card detect switch	*/

/* Port Controls */
#define CS_HIGH()					SPIMMC_PORT_CS->BSRRL  = SPIMMC_PIN_CS		/* MMC CS = H */
#define CS_LOW()					SPIMMC_PORT_CS->BSRRH  = SPIMMC_PIN_CS		/* MMC CS = L */

#ifdef USE_PWRCTRL
	#warning "use MMC power control"
	#define SPIMMC_PWR              GPIOD
	#define SPIMMC_CLK_PWR 			RCC_AHB1Periph_GPIOD
	#define SPIMMC_PIN_PWR          GPIO_Pin_10
	#define SPIMMC_PWR_CLK(x)		RCC_AHB1PeriphClockCmd(SPIMMC_CLK_PWR ,x);
	#define PWR_ON()        		(SPIMMC_PWR->BSRRH = GPIO_Pin_PWR)
	#define PWR_OFF()       		(SPIMMC_PWR->BSRRL = GPIO_Pin_PWR)
	#define PWR_ISON()      		((GPIO_ReadOutputDataBit(SPIMMC, SPIMMC_PIN_PWR) == Bit_SET ) ? 0 : 1)
#else
	//#warning "none MMC power control"
	#define PWR_ON()
	#define PWR_OFF()
	#define PWR_ISON()				1
#endif

/* Function Prototypes */



#ifdef __cplusplus
}
#endif

#endif /*  __MMC_STM32F4_H */
