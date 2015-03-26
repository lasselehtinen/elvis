<?php

class ElvisUnitTest extends Orchestra\Testbench\TestCase
{
    // Override package service provider and alias
    protected function getPackageProviders()
    {
        return array('Lasselehtinen\Elvis\ElvisServiceProvider');
    }

    protected function getPackageAliases()
    {
        return array('Elvis' => 'Lasselehtinen\Elvis\Facades\Elvis');
    }    

    /**
     * Tests basic query URI with all three parameters
     *
     * @covers Elvis::getQueryUrl
     * @group unit
     * @return void
     */
    public function testBasicQueryAllParametersUri()
    {
        //
        $queryParameters = array(
            'q'        => 'gtin:123',
            'async'    => true
        );

        $metadata = array('publicationName:Test');
        $uri = Elvis::getQueryUrl('updatebulk', $queryParameters, $metadata);

        // Form expected URI
        $expected_uri = Config::get('elvis.api_endpoint_uri') . 'updatebulk?q=gtin:123&async=1&metadata=["publicationName:Test"]';
        $this->assertEquals(urldecode($uri), $expected_uri);
    }

    /**
     * Tests search URI with facet data
     *
     * @covers Elvis::getQueryUrl
     * @group unit
     * @return void
     */
    public function testSearchWithFacetsUri()
    {
        $queryParameters = array(
            'facets'   => 'tags,extension',
        );

        $uri = Elvis::getQueryUrl('search', $queryParameters);

        // Form expected URI
        $expected_uri = Config::get('elvis.api_endpoint_uri') . 'search?facets=tags,extension';
        $this->assertEquals(urldecode($uri), $expected_uri);
    }

    /**
     * Tests search URI with selected facet data.
     *
     * @covers Elvis::getQueryUrl
     * @group unit
     * @return void
     */
    public function testSearchWithSelectedFacetsUri()
    {
        $queryParameters = array(
            'facetSelection' => ['tags' => 'beach', 'extension' => 'jpg,png'],
        );

        $uri = Elvis::getQueryUrl('search', $queryParameters);

        // Form expected URI
        $expected_uri = Config::get('elvis.api_endpoint_uri') . 'search?facet.tags.selection=beach&facet.extension.selection=jpg,png';
        $this->assertEquals(urldecode($uri), $expected_uri);
    }

    /**
     * Tests basic query URI with all three parameters
     *
     * @covers Elvis::getQueryUrl
     * @group unit
     * @return void
     */
    public function testZipUri()
    {
        // Form zip parameters
        $zipParameters = array(
            'filename'      => 'filename.zip',
            'downloadKind'  => 'preview',
            'assetIds'      => 'assetId1,assetId2'
        );

        $uri = Elvis::getQueryUrl('zip', $zipParameters);

        // Form expected URI
        $hostname = str_replace('services/', '', Config::get('elvis.api_endpoint_uri'));
        $expected_uri =  $hostname . 'zip/filename.zip?downloadKind=preview&assetIds=assetId1,assetId2';
        $this->assertEquals(urldecode($uri), $expected_uri);
    }

    /**
     * Tests URI for checkout
     *
     * @covers Elvis::getQueryUrl
     * @group unit
     * @return void
     */
    public function testCheckoutUri()
    {
        // Form zip parameters
        $checkoutParameters = array('assetId' => 'assetId');

        $uri = Elvis::getQueryUrl('checkout', $checkoutParameters);

        // Form expected URI
        $expected_uri = Config::get('elvis.api_endpoint_uri') . 'checkout/assetId';
        $this->assertEquals(urldecode($uri), $expected_uri);
    }
}
