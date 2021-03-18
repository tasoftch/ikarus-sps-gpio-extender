<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Ikarus\SPS\I2C;


use TASoft\Bus\I2C;

class Extender_16GPIO_MCP23017
{
	const ADDRESS_1 = 0x20;
	const ADDRESS_2 = 0x21;
	const ADDRESS_3 = 0x22;
	const ADDRESS_4 = 0x23;
	const ADDRESS_5 = 0x24;
	const ADDRESS_6 = 0x25;
	const ADDRESS_7 = 0x26;
	const ADDRESS_8 = 0x27;

	const DEFAULT_ADDRESS = self::ADDRESS_1;

	const PIN_A_0 = 0;
	const PIN_A_1 = 1;
	const PIN_A_2 = 2;
	const PIN_A_3 = 3;
	const PIN_A_4 = 4;
	const PIN_A_5 = 5;
	const PIN_A_6 = 6;
	const PIN_A_7 = 7;

	const PIN_B_0 = 8;
	const PIN_B_1 = 9;
	const PIN_B_2 = 10;
	const PIN_B_3 = 11;
	const PIN_B_4 = 12;
	const PIN_B_5 = 13;
	const PIN_B_6 = 14;
	const PIN_B_7 = 15;

	const SETUP_INPUT = 1<<0;
	const SETUP_OUTPUT = 1<<2;

	const SETUP_PULLUP = 1<<1;
	const SETUP_ACTIVE_LOW = 1<<3;

	const SETUP_RAISING_INTERRUPT = 1<<6;
	const SETUP_FALLING_INTERRUPT = 1<<7;


	/** @var I2C */
	private $bus;

	private $INP = 0x0000;
	private $OUTP = 0x0000;
	private $OUTC = 0x0;
	private $DIR = 0xFFFF;
	private $ACT_LOW = 0x0;

	const VALUE_LOW = 0;
	const VALUE_HIGH = 1;
	const VALUE_ERROR = -1;

	/**
	 * DAC_MCP4625 constructor.
	 * @param I2C $bus
	 */
	public function __construct(I2C $bus)
	{
		$this->bus = $bus;
	}

	/**
	 * Gets the i2c instance
	 * @return I2C
	 */
	public function getBus(): I2C
	{
		return $this->bus;
	}

	/**
	 * Setup a pinout to work with.
	 *
	 * @param array $pinout
	 * @param bool $updateChip
	 */
	public function setupPins(array $pinout, bool $updateChip = true) {
		$pull = 0x0;
		$int = 0x0;
		$def = 0x0;
		$defCon = 0x0;

		foreach($pinout as $pin => $flags) {
			if($flags & static::SETUP_ACTIVE_LOW)
				$this->ACT_LOW |= 1<<$pin;

			if($flags & static::SETUP_INPUT) {
				$this->INP |= 1<<$pin;

				if($flags & static::SETUP_PULLUP)
					$pull |= 1<<$pin;
				if($flags & static::SETUP_RAISING_INTERRUPT || $flags & static::SETUP_FALLING_INTERRUPT) {
					$int |= 1<<$pin;
					if(!($flags & static::SETUP_RAISING_INTERRUPT && $flags & static::SETUP_FALLING_INTERRUPT)) {
						$defCon |= 1<<$pin;
						if($flags & static::SETUP_FALLING_INTERRUPT)
							$def |=1<<$pin;
					}
				}
			} elseif($flags & static::SETUP_OUTPUT) {
				$this->DIR &= ~(1<<$pin);
				$this->OUTP |= 1<<$pin;
			}
		}

		if($updateChip) {
			// Set directions: IODIR A => 0x00, B => 0x10
			$this->bus->write(0x00, [$this->DIR & 0xFF]);
			$this->bus->write(0x01, [($this->DIR >> 8) & 0xFF]);

			$this->bus->write(0x04, [$int & 0xFF]);
			$this->bus->write(0x05, [($int >> 8) & 0xFF]);
			$this->bus->write(0x06, [$def & 0xFF]);
			$this->bus->write(0x07, [($def >> 8) & 0xFF]);
			$this->bus->write(0x08, [$defCon & 0xFF]);
			$this->bus->write(0x09, [($defCon >> 8) & 0xFF]);

			$this->bus->write(0x0C, [$pull & 0xFF]);
			$this->bus->write(0x0D, [($pull >> 8) & 0xFF]);
		}
	}

	/**
	 * Helps to mask a bitwise pin selector
	 *
	 * @param int ...$pins
	 * @return int
	 */
	public static function makePinMask(...$pins): int
	{
		$mask = 0;
		foreach($pins as $pin) {
			if($pin < 0 || $pin > 15)
				continue;

			$mask |= 1<<$pin;
		}
		return $mask;
	}

	/**
	 * Reads all inputs from the MCP GPIO A and B.
	 * If no pins required from B (or A) the chip won't be asked.
	 *
	 * @param int $pins
	 * @return int
	 */
	public function digitalRead(int $pins = 0xFFFF) {
		$r = 0;
		if($pins & 0xFF) {
			$this->bus->writeRegister(0x12);
			$b = $this->bus->readByte() ^ $this->ACT_LOW;
			$r = $b & ($pins & $this->INP);
		}
		if($pins & 0xFF00) {
			$this->bus->writeRegister(0x13);
			$b = $this->bus->readByte() ^ ($this->ACT_LOW>>8);
			$r |= ($b & (($pins & $this->INP) >> 8)) << 8;
		}
		return $r;
	}

	/**
	 * Reads the state from a single input pin.
	 *
	 * @param int $pin
	 * @return int
	 */
	public function digitalReadPin(int $pin): int {
		$pin = 1<<$pin;
		if($this->DIR & $pin) {
			if($pin > 0xFF)
				$this->bus->writeRegister(0x13);
			else
				$this->bus->writeRegister(0x12);
			$b = $this->bus->readByte() ^ $this->ACT_LOW;
			return $b & $pin ? static::VALUE_HIGH : static::VALUE_LOW;
		}
		return static::VALUE_ERROR;
	}

	/**
	 * Writes values to the output pins.
	 *
	 * @param int $pins
	 * @param int $values
	 */
	public function digitalWrite(int $pins = 0xFFFF, int $values = 0) {
		$pins = $pins & $this->OUTP;
		$values = ($this->OUTC & ~$pins) | $pins & $values;
		if($pins & 0xFF) {
			$this->bus->write(0x12, [ $w = ($values & 0xFF) ^ $this->ACT_LOW ]);
		}
		if($pins & 0xFF00) {
			$this->bus->write(0x13, [ ($values>>8 & 0xFF) ^ ($this->ACT_LOW>>8) ]);
		}
		$this->OUTC = $values;
	}

	/**
	 * Writes to a single pin
	 *
	 * @param int $pin
	 * @param int $value
	 * @return int
	 */
	public function digitalWritePin(int $pin, int $value): int
	{
		$pin = 1<<$pin;
		if($this->OUTP & $pin) {
			$values = ($this->OUTC & ~$pin) & $this->OUTP; // Capture all other values
			$values |= $value > static::VALUE_LOW ? $pin : 0; // Add the output option if value is high

			if($pin>0xFF) {
				$this->bus->write(0x13, [ ($values>>8 & 0xFF) ^ ($this->ACT_LOW>>8) ]);
			} else {
				$this->bus->write(0x12, [ $w = ($values & 0xFF) ^ $this->ACT_LOW ]);
			}
			$this->OUTC = $values;
			return $value;
		}
		return static::VALUE_ERROR;
	}
}