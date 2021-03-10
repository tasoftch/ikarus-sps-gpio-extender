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

class Extender_8GPIO_PCF_8574
{
	const PIN_0 = 0;
	const PIN_1 = 1;
	const PIN_2 = 2;
	const PIN_3 = 3;

	const PIN_4 = 4;
	const PIN_5 = 5;
	const PIN_6 = 6;
	const PIN_7 = 7;

	const VAL_HIGH = 1;
	const VAL_LOW = 0;


	/** @var I2C */
	private $i2c;

	private $_PIN=0, $_PORT=0xFF;

	/**
	 * @return I2C
	 */
	public function getBus(): I2C
	{
		return $this->i2c;
	}

	/**
	 * PCF_8574_8_Pins constructor.
	 * @param I2C $i2c
	 */
	public function __construct(I2C $i2c)
	{
		$this->i2c = $i2c;
	}

	/**
	 * Puts all pins to inputs
	 */
	public function cleanup() {
		$this->i2c->write(0xFF, []);
	}

	/**
	 * Writes HIGH or LOW to a specific pin
	 *
	 * @param int $pin
	 * @param int $value
	 */
	public function digitalWrite(int $pin, int $value) {
		if($value)
			$this->_PORT |= (1<<$pin);
		else
			$this->_PORT &= ~(1<<$pin);
		$this->send();
	}

	/**
	 * Reads the digital input state from a pin
	 *
	 * @param int $pin
	 * @return int
	 */
	public function digitalRead(int $pin) {
		$this->read();
		return ($this->_PIN & (1<<$pin)) ? self::VAL_HIGH : self::VAL_LOW;
	}

	/**
	 * Writes a full qualified port specification to the chip
	 *
	 * @param int $value
	 */
	public function write(int $value) {
		$this->_PORT = $value;
		$this->send();
	}

	/**
	 * Toggle value of an output pin.
	 *
	 * @param int $pin
	 */
	public function toggle(int $pin) {
		$this->_PORT ^= (1<<$pin);
		$this->send();
	}

	/**
	 * Reads all pins from the chip
	 *
	 * @return int
	 */
	public function read() {
		$this->_PIN = $this->i2c->readByte();
		return $this->_PIN;
	}

	/**
	 * Sends current setup to the chip
	 */
	public function send() {
		$this->i2c->write($this->_PORT & 0xFF, []);
	}
}