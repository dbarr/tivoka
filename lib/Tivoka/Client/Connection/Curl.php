<?php
/**
 * Tivoka - JSON-RPC done right!
 * Copyright (c) 2011-2012 by Marcel Klehr <mklehr@gmx.net>
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package  Tivoka
 * @author Marcel Klehr <mklehr@gmx.net>
 * @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
 * @copyright (c) 2011-2012, Marcel Klehr
 */

namespace Tivoka\Client\Connection;
use Tivoka\Client\BatchRequest;
use Tivoka\Exception;
use Tivoka\Client\Request;

/**
 * HTTP connection
 * @package Tivoka
 */
class Curl extends AbstractConnection {

    public $target;
    public $headers = array();
    public $context;
    public $ch;

    /**
     * Constructs connection
     * @access private
     * @param string $target URL
     */
    public function __construct($target) {
        //validate url...
        if (!filter_var($target, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            throw new Exception\Exception('Valid URL (scheme://domain[/path][/file]) required.');
        }

        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_URL, $target);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        $this->setHeader('Content-Type', 'application/json');
        $this->setHeader('Connection', 'Close');
    }

    /**
     * Sets the HTTP headers to use for upcoming send requests
     * @param string label of header
     * @param string value of header
     * @return Http Self instance
     */
    public function setHeader($label, $value) {
        $this->headers[$label] = $value;
        return $this;
    }

    /**
     * Sets the stream context to use for upcoming send requests
     * @param resource a stream context resource returned by stream_context_create()
     * @return Http Self instance
     */
    public function setCurlOptions(array $opts) {
        curl_setopt_array($this->ch, $opts);
        return $this;
    }

    /**
     * Sends a JSON-RPC request
     * @param Request $request A Tivoka request
     * @return Request if sent as a batch request the BatchRequest object will be returned
     */
    public function send(Request $request) {
        if(func_num_args() > 1 ) $request = func_get_args();
        if(is_array($request)) {
            $request = new BatchRequest($request);
        }
        
        if(!($request instanceof Request)) throw new Exception\Exception('Invalid data type to be sent to server');
        
        $content = $request->getRequest($this->spec);

        $this->setHeader('Content-Length', strlen($content));

        $headers = [];
        array_walk($this->headers, function($v, $k) use (&$headers) { $headers[] = sprintf('%s: %s', $k, $v); });

        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->ch, CURLOPT_CERTINFO, true);
        curl_setopt($this->ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($this->ch);

        if (curl_error($this->ch))
            throw new Exception\ConnectionException('Connection failed: ' . curl_error($this->ch));

        $curlinfo = curl_getinfo($this->ch);

        $request->setResponse($response);
//        $request->setHeaders($curlinfo['request_header']);
        return $request;
    }
}
