<?php namespace Helper;

use Codeception\Module;
use Flow\JSONPath\JSONPath;

class Api extends Module
{
    use CommonJsonPath, CommonCollection;

    //-----------------------------------
    // HTTP
    //-----------------------------------

    public function sendMultiple($requests, $callback)
    {
        $module = $this->moduleContainer->getModule('REST');

        array_map(function ($request) use ($module, $callback) {

            switch ($request[0]) {
                case 'GET': $module->sendGET($request[1]); break;
                case 'POST': $module->sendPOST($request[1], $request[2]); break;
                case 'PATCH': $module->sendPATCH($request[1], $request[2]); break;
                case 'DELETE':
                    if (count($request) > 2) {
                        $module->sendDELETE($request[1], $request[2]);
                    } else {
                        $module->sendDELETE($request[1]);
                    }
                    break;
            }

            $callback($request);
        }, $requests);
    }

    //-----------------------------------
    // response
    //-----------------------------------

    public function grabResponseAsJson()
    {
        return json_decode($this->getModule('Laravel5')->_getResponseContent(), true);
    }

    public function grabResponseJsonPath($json_path)
    {
        $response = $this->grabResponseAsJson();
        $result = (new JSONPath($response))->find($json_path);
        return $result->data();
    }

    //-----------------------------------
    // misc
    //-----------------------------------

    // contains

    public function seeResponseContains($text)
    {
        $this->assertContains($text, $this->getModule('Laravel5')->_getResponseContent(), "Response does not contain {$text}");
    }

    //-----------------------------------
    // JSON Path
    //-----------------------------------

    // match

    public function seeResponseJsonPath($json_path)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPath($response, $json_path);
    }

    public function seeNotResponseJsonPath($json_path)
    {
        $response = $this->grabResponseAsJson();
        $this->seeNotJsonPath($response, $json_path);
    }

    // length

    public function seeResponseJsonPathLength($json_path, $expected_length)
    {
        $response = $this->grabResponseJsonPath($json_path);
        $this->seeJsonPathLength($response, $json_path, $expected_length);
    }

    // same

    public function seeResponseJsonPathSame($json_path, $expected)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathSame($response, $json_path, $expected);
    }

    public function seeResponseJsonPathNotSame($json_path, $expected)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathNotSame($response, $json_path, $expected);
    }

    // null

    public function seeResponseJsonPathNull($json_path)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathNull($response, $json_path);
    }

    public function seeResponseJsonPathNotNull($json_path)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathNotNull($response, $json_path);
    }

    // empty

    public function seeResponseJsonPathEmpty($json_path)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathEmpty($response, $json_path);
    }

    public function seeResponseJsonPathNotEmpty($json_path)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathNotEmpty($response, $json_path);
    }

    // data type

    public function seeResponseJsonPathType($json_path, $type)
    {
        $response = $this->getModule('Laravel5')->_getResponseContent();
        $this->seeJsonPathType(json_decode($response, true), $json_path, $type);
    }

    // regex

    public function seeResponseJsonPathRegex($json_path, $regex)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathRegex($response, $json_path, $regex);
    }

    public function seeResponseJsonPathNotRegex($json_path, $regex)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathNotRegex($response, $json_path, $regex);
    }

    // regex : all once
    // asserts that each expression in regex array is matched once with a in json path array item

    public function seeResponseJsonPathRegexMatchAllOnce($json_path, $regex)
    {
        $response = $this->grabResponseAsJson();
        $this->seeJsonPathRegexMatchAllOnce($response, $json_path, $regex);
    }
}
