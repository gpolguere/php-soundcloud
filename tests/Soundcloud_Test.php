<?php
require_once 'Soundcloud_Test_Helper.php';

class Soundcloud_Test extends PHPUnit_Framework_TestCase {

    protected $soundcloud;

    function setUp() {
        $this->soundcloud = new Services_Soundcloud_Expose(
            '1337',
            '1337',
            'http://soundcloud.local/callback'
        );
    }

    function tearDown() {
        $this->soundcloud = null;
    }

    function testVersionFormat() {
        self::assertRegExp(
            '/^[0-9]+\.[0-9]+\.[0-9]+$/',
            Services_Soundcloud_Version::get()
        );
    }

    function testGetUserAgent() {
        self::assertRegExp(
            '/^PHP\-SoundCloud\/[0-9]+\.[0-9]+\.[0-9]+$/',
            $this->soundcloud->getUserAgent()
        );
    }

    function testApiVersion() {
        self::assertEquals(1, $this->soundcloud->getApiVersion());
    }

    function testGetAudioMimeTypes() {
        $supportedExtensions = array(
            'aac' => 'video/mp4',
            'aiff' => 'audio/x-aiff',
            'flac' => 'audio/flac',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/x-wav'
        );
        $unsupportedExtensions = array('gif', 'html', 'jpg', 'mp4', 'xml', 'xspf');

        foreach ($supportedExtensions as $extension => $mimeType) {
            self::assertEquals(
                $mimeType,
                $this->soundcloud->getAudioMimeType($extension)
            );
        }

        foreach ($unsupportedExtensions as $extension => $mimeType) {
            $this->setExpectedException('Services_Soundcloud_Unsupported_Audio_Format_Exception');

            $this->soundcloud->getAudioMimeType($extension);
        }
    }

    function testGetAuthorizeUrl() {
        self::assertEquals(
            'https://soundcloud.com/connect?client_id=1337&redirect_uri=http%3A%2F%2Fsoundcloud.local%2Fcallback&response_type=code',
            $this->soundcloud->getAuthorizeUrl()
        );
    }

    function testGetAuthorizeUrlWithCustomQueryParameters() {
        self::assertEquals(
            'https://soundcloud.com/connect?client_id=1337&redirect_uri=http%3A%2F%2Fsoundcloud.local%2Fcallback&response_type=code&foo=bar',
            $this->soundcloud->getAuthorizeUrl(array('foo' => 'bar'))
        );

        self::assertEquals(
            'https://soundcloud.com/connect?client_id=1337&redirect_uri=http%3A%2F%2Fsoundcloud.local%2Fcallback&response_type=code&foo=bar&bar=foo',
            $this->soundcloud->getAuthorizeUrl(array('foo' => 'bar', 'bar' => 'foo'))
        );
    }

    function testGetAccessTokenUrl() {
        self::assertEquals(
            'https://api.soundcloud.com/oauth2/token',
            $this->soundcloud->getAccessTokenUrl()
        );
    }

    function testSetAccessToken() {
        $this->soundcloud->setAccessToken('1337');

        self::assertEquals('1337', $this->soundcloud->getAccessToken());
    }

    function testSetDevelopment() {
        $this->soundcloud->setDevelopment(true);

        self::assertTrue($this->soundcloud->getDevelopment());
    }

    function testSetRedirectUri() {
        $this->soundcloud->setRedirectUri('http://soundcloud.local/callback');

        self::assertEquals(
            'http://soundcloud.local/callback',
            $this->soundcloud->getRedirectUri()
        );
    }

    function testDefaultResponseFormat() {
        self::assertEquals(
            'application/json',
            $this->soundcloud->getResponseFormat()
        );
    }

    function testSetResponseFormatHtml() {
        $this->setExpectedException('Services_Soundcloud_Unsupported_Response_Format_Exception');

        $this->soundcloud->setResponseFormat('html');
    }

    function testSetResponseFormatAll() {
        $this->soundcloud->setResponseFormat('*');

        self::assertEquals(
            '*/*',
            $this->soundcloud->getResponseFormat()
        );
    }

    function testSetResponseFormatJson() {
        $this->soundcloud->setResponseFormat('json');

        self::assertEquals(
            'application/json',
            $this->soundcloud->getResponseFormat()
        );
    }

    function testSetResponseFormatXml() {
        $this->soundcloud->setResponseFormat('xml');

        self::assertEquals(
            'application/xml',
            $this->soundcloud->getResponseFormat()
        );
    }

    function testResponseCodeSuccess() {
        self::assertTrue($this->soundcloud->validResponseCode(200));
    }

    function testResponseCodeRedirect() {
        self::assertFalse($this->soundcloud->validResponseCode(301));
    }

    function testResponseCodeClientError() {
        self::assertFalse($this->soundcloud->validResponseCode(400));
    }

    function testResponseCodeServerError() {
        self::assertFalse($this->soundcloud->validResponseCode(500));
    }

    function testBuildDefaultHeaders() {
        self::assertEquals(
            array('Accept: application/json'),
            $this->soundcloud->buildDefaultHeaders()
        );
    }

    function testBuildDefaultHeadersWithAccessToken() {
        $this->soundcloud->setAccessToken('1337');

        self::assertEquals(
            array('Accept: application/json', 'Authorization: OAuth 1337'),
            $this->soundcloud->buildDefaultHeaders()
        );
    }

    function testBuildUrl() {
        self::assertEquals(
            'https://api.soundcloud.com/v1/me',
            $this->soundcloud->buildUrl('me')
        );
    }

    function testBuildUrlWithQueryParameters() {
        self::assertEquals(
            'https://api.soundcloud.com/v1/tracks?q=rofl+dubstep',
            $this->soundcloud->buildUrl(
                'tracks',
                array('q' => 'rofl dubstep')
            )
        );

        self::assertEquals(
            'https://api.soundcloud.com/v1/tracks?q=rofl+dubstep&filter=public',
            $this->soundcloud->buildUrl(
                'tracks',
                array('q' => 'rofl dubstep', 'filter' => 'public')
            )
        );
    }

    function testBuildUrlWithDevelopmentDomain() {
        $this->soundcloud->setDevelopment(true);

        self::assertEquals(
            'https://api.sandbox-soundcloud.com/v1/me',
            $this->soundcloud->buildUrl('me')
        );
    }

    function testBuildUrlWithoutApiVersion() {
        self::assertEquals(
            'https://api.soundcloud.com/me',
            $this->soundcloud->buildUrl('me', null, false)
        );
    }

    function testBuildUrlWithAbsoluteUrl() {
        self::assertEquals(
            'https://api.soundcloud.com/me',
            $this->soundcloud->buildUrl('https://api.soundcloud.com/me')
        );
    }

    /**
     * @dataProvider dataProviderHttpHeaders
     */
    function testParseHttpHeaders($rawHeaders, $expectedHeaders) {
        $parsedHeaders = $this->soundcloud->parseHttpHeaders($rawHeaders);

        foreach ($parsedHeaders as $key => $val) {
            self::assertEquals($val, $expectedHeaders[$key]);
        }
    }

    function testSoundcloudMissingConsumerKeyException() {
        $this->setExpectedException('Services_Soundcloud_Missing_Client_Id_Exception');

        $soundcloud = new Services_Soundcloud('', '');
    }

    function testSoundcloudInvalidHttpResponseCodeException() {
        $this->setExpectedException('Services_Soundcloud_Invalid_Http_Response_Code_Exception');

        $this->soundcloud->get('me');
    }

    /**
     * @dataProvider dataProviderSoundcloudInvalidHttpResponseCode
     */
    function testSoundcloudInvalidHttpResponseCode($expectedHeaders) {
        try {
            $this->soundcloud->get('me');
        } catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
            self::assertEquals(
                '{"error":"401 - Unauthorized"}',
                $e->getHttpBody()
            );

            self::assertEquals(401, $e->getHttpCode());

            foreach ($expectedHeaders as $key => $val) {
                self::assertEquals(
                    $val,
                    $this->soundcloud->getHttpHeader($key)
                );
            }
        }
    }

    static function dataProviderHttpHeaders() {
        $rawHeaders = <<<'HEADERS'
HTTP/1.1 200 OK
Date: Wed, 17 Nov 2010 15:39:52 GMT
Cache-Control: public
Content-Type: text/html; charset=utf-8
Content-Encoding: gzip
Server: foobar
Content-Length: 1337
HEADERS;
        $expectedHeaders = array(
            'date' => 'Wed, 17 Nov 2010 15:39:52 GMT',
            'cache_control' => 'public',
            'content_type' => 'text/html; charset=utf-8',
            'content_encoding' => 'gzip',
            'server' => 'foobar',
            'content_length' => '1337'
        );

        return array(array($rawHeaders, $expectedHeaders));
    }

    static function dataProviderSoundcloudInvalidHttpResponseCode() {
        $expectedHeaders = array(
            'server' => 'nginx',
            'content_type' => 'application/json; charset=utf-8',
            'connection' => 'keep-alive',
            'cache_control' => 'no-cache',
            'content_length' => '30'
        );

        return array(array($expectedHeaders));
    }

}
