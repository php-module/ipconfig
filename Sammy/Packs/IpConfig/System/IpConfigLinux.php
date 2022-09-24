<?php
/**
 * @version 2.0
 * @author Sammy
 *
 * @keywords Samils, ils, php framework
 * -----------------
 * @package Sammy\Packs\IpConfig\System
 * - Autoload, application dependencies
 *
 * MIT License
 *
 * Copyright (c) 2020 Ysare
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Sammy\Packs\IpConfig\System {
  /**
   * Make sure the module base internal trait is not
   * declared in the php global scope defore creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!trait_exists ('Sammy\Packs\IpConfig\System\IpConfigLinux')) {
  /**
   * @trait IpConfigLinux
   * Base internal trait for the
   * IpConfig\System module.
   * -
   * This is (in the ils environment)
   * an instance of the php module,
   * wich should contain the module
   * core functionalities that should
   * be extended.
   * -
   * For extending the module, just create
   * an 'exts' directory in the module directory
   * and boot it by using the ils directory boot.
   * -
   */
  trait IpConfigLinux {
    /**
     * @method array get ip config in a linux OS
     */
    public function getLinuxIpConfig () {
      $ipConfigAddresses = $this->getLinuxIpConfigAddresses ();

      if (is_file ($this->outputFilePath)) {
        @unlink ($this->outputFilePath);
      }

      return ($ipConfigAddresses);
    }

    /**
     * @method array getLinuxIpConfigAddresses
     */
    protected function getLinuxIpConfigAddresses () {
      $this->executeSystemIpConfig ('linuxsd');

      $outputFileHandle = @fopen ($this->outputFilePath, 'r');

      $lineStartRe = '/^\s*([0-9]+):\s*/';
      $ipConfigAddresses = [];

      while (!feof ($outputFileHandle)) {
        $outputFileLine = fgets ($outputFileHandle);

        if (!empty (trim ($outputFileLine))
          && preg_match ($lineStartRe, $outputFileLine)) {
          array_push ($ipConfigAddresses, [
            preg_replace ($lineStartRe, '', $outputFileLine)
          ]);
        }

        if (!empty (trim ($outputFileLine))
          && preg_match ('/^(\s+)/', $outputFileLine)) {
          $ipConfigAddressId = (-1 + count ($ipConfigAddresses));

          if (isset ($ipConfigAddresses [$ipConfigAddressId])
            && is_array ($ipConfigAddresses [$ipConfigAddressId])) {
            array_push ($ipConfigAddresses [$ipConfigAddressId], $outputFileLine);
          }
        }
      }

      @fclose ($outputFileHandle);

      $ipConfigAddressesMap = [];

      /**
       *
       * [connection-specificDNSSuffix] => Array
       *
       * [link-localIPv6Address] => Array
       *
       * [iPv4Address] => Array
       *
       * [subnetMask] => Array
       *
       * [defaultGateway] => Array
       *
       */
      foreach ($ipConfigAddresses as $index => $ipConfigAddressData) {
        $ipConfigAddresses [$index] = $this->parseIpConfigAddressData (join (' ', $ipConfigAddressData));

        foreach ($ipConfigAddresses [$index] as $key => $value) {
          if (isset ($ipConfigAddressesMap [$key])) {
            array_push ($ipConfigAddressesMap [$key], $value);
          } else {
            $ipConfigAddressesMap [$key] = [$value];
          }
        }
      }

      return array_merge ($ipConfigAddressesMap, $ipConfigAddresses);
    }

    /**
     * @method array parseIpConfigAddressData
     */
    private function parseIpConfigAddressData ($ipConfigAddressData) {
      $configAddressData = [];

      $ipConfigAddressDataLines = preg_split ('/\n+/', $ipConfigAddressData);

      foreach ($ipConfigAddressDataLines as $ipConfigAddressDataLine) {
        if (empty ($ipConfigAddressDataLine)) {
          continue;
        }

        $ipConfigAddressDataMap = preg_split ('/\s+/', trim ($ipConfigAddressDataLine));

        $ipConfigAddressDataMapLen = count ($ipConfigAddressDataMap);

        for ($i = 0; $i < $ipConfigAddressDataMapLen; $i += 2) {
          $configAddressDataKey = $ipConfigAddressDataMap [$i];
          $configAddressDataVal = null;

          if (isset ($ipConfigAddressDataMap [$i + 1])) {
            $configAddressDataVal = $ipConfigAddressDataMap [$i + 1];
          }

          $configAddressData [$configAddressDataKey] = $configAddressDataVal;
        }
      }

      return $configAddressData;
    }
  }}
}
