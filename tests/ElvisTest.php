<?php

class ElvisTest extends Orchestra\Testbench\TestCase
{
    protected $sessionId;
    protected $assetId;

    public function setUp()
    {
        parent::setUp();

        // Get session Id to user in the queries
        $this->sessionId = Elvis::login();

        // Store asset id for various tests        
        $search_results = Elvis::search($this->sessionId, 'filename:composer.json AND assetModifier:' . Config::get('elvis::username'));
        
        if($search_results->totalHits > 0) {
            $this->assetId = $search_results->hits[0]->id;    
        }
        
    }

    public function tearDown()
    {
        parent::tearDown();

        // Log out from Elvis
        $logout = Elvis::logout($this->sessionId);
    }

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
     * Tests that basic login is succesfull and session id is returned
     *
     * @return void
     */
    public function testLogin()
    {
        // Test that login is succesful and we sessionId
        $this->assertInternalType('string', $this->sessionId);
    }

     /**
     * Tests that basic login with incorrect username and password returns correct error
     *
     * @return void
     */
    public function testLoginWithIncorrectUsernameAndPassword()
    {
        // Test that login i
        $this->setExpectedException(
          'Symfony\Component\HttpKernel\Exception\HttpException', 'Invalid username or password'
        );

        // Set incorrent username and password
        Config::set('elvis::username', 'incorrect_username');
        Config::set('elvis::password', 'incorrect_password');

        // Try login
        $sessionId = Elvis::login();
    }

    /**
     * Tests that basic login is succesfull and session id is returned
     *
     * @return void
     */
    public function testProfile()
    {
        // Fetch profile
        $profile = Elvis::profile($this->sessionId);

        // Chech that profile is in correct form
        $this->assertInternalType('object', $profile);

        // Check that we have authorieties and user groups
        $this->assertInternalType('array', $profile->authorities);
        $this->assertInternalType('array', $profile->groups);

        // Check that username is the same we used to log in
        $this->assertEquals($profile->username, Config::get('elvis::username'));
    }

    /**
     * Tests that creating a file and assigning metadata to it works fine
     *
     * @return void
     */
    public function testCreate()
    {
        // Set additional metadata
        $metadata = array('gtin' => '123456', 'creatorName' => 'Test');

        // Create a file
        $create = Elvis::create($this->sessionId, './composer.json', $metadata);

        // Chech that response is in correct form
        $this->assertInternalType('object', $create);

        // Check that the given metadata is set on the object
        foreach ($metadata as $key => $value) {
            $this->assertEquals($create->metadata->$key, $value);
        }
    }

    /**
     * Tests that search functions works with different parameters
     *
     * @return void
     */
    public function testSearch()
    {
        // General search without any parameters
        $search_results = Elvis::search($this->sessionId, '*:*');

        // Check that defaults are OK (start = 0 and num = 50)
        $this->assertEquals($search_results->firstResult, 0);
        $this->assertEquals($search_results->maxResultHits, 50);

        // More spesified search
        $search_results = Elvis::search($this->sessionId, '*:*', 10, 20, '', 'metadataComplete', true);

        // Check that start and num are reflected in the response
        $this->assertEquals($search_results->firstResult, 10);
        $this->assertEquals($search_results->maxResultHits, 20);

        // Check metadataToReturn = 'metadataComplete' is returned in results
        $this->assertInternalType('string', $search_results->hits[0]->metadata->metadataComplete);

        // Check that requestSecret parameter is added to the response URL's
        $this->assertContains('&requestSecret=', $search_results->hits[0]->originalUrl);
    }

     /**
     * Tests that browse function returns results
     *
     * @return void
     */
    public function testBrowse()
    {
        // General search without any parameters
        $browse_results = Elvis::browse($this->sessionId, '.');
        $this->assertInternalType('array', $browse_results);
    }

    /**
    * Tests for metadata update function
    *
    * @return void
    */
    public function testUpdate()
    {        
        // Update one metadata field
        $update = Elvis::update($this->sessionId, $this->assetId, null, ['gtin' => '1234567890']);

        // Check we that get empty object as return
        $this->assertInternalType('object', $update);

        // Get the asset info again and check that the metadata is updated
        $search_results = Elvis::search($this->sessionId, 'id:'.$this->assetId);
        $this->assertEquals($search_results->hits[0]->metadata->gtin, 1234567890);
    }

    /**
    *
    * Tests for the bulk update function
    *
    * @return void
    */
    public function testBulkUpdate()
    {
        // Run update bulk in async so we wait for the process to finish
        $updatebulk = Elvis::updatebulk($this->sessionId, 'id:' . $this->assetId, array('gtin' => '1234567890123'), false);

        // Check that we processedCount 1
        $this->assertEquals($updatebulk->processedCount, 1);

        // Refetch asset and check that metadata is updated
        $search_results = Elvis::search($this->sessionId, 'id:'.$this->assetId);
        $this->assertEquals($search_results->hits[0]->metadata->gtin, 1234567890123);


    }

}
