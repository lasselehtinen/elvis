<?php

class ElvisTest extends Orchestra\Testbench\TestCase
{
    protected $sessionId;
    protected $assetId;

    // Override package service provider and alias
    protected function getPackageProviders()
    {
        return array('Lasselehtinen\Elvis\ElvisServiceProvider');
    }

    protected function getPackageAliases()
    {
        return array('Elvis' => 'Lasselehtinen\Elvis\Facades\Elvis');
    }

    public function setUp()
    {
        parent::setUp();

        // Get session Id to user in the queries
        $this->sessionId = Elvis::login();
        
        // Upload a randon file to Elvis for various tests
        $temporaryFilename = $tmpfname = tempnam("/tmp", "ElvisTest");

        // Create a file and store asset id
        $create = Elvis::create($this->sessionId, $temporaryFilename);
        $this->assetId = $create->id;
        
        // Remove temporary file
        unlink($temporaryFilename);
    }

    public function tearDown()
    {
        // Remove asset from Elvis
        $remove = Elvis::remove($this->sessionId, null, array($this->assetId), null, false);

        // Log out from Elvis
        $logout = Elvis::logout($this->sessionId);
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

        // Set incorrect username and password
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
        $searchResults = Elvis::search($this->sessionId, '*:*');

        // Check that defaults are OK (start = 0 and num = 50)
        $this->assertEquals($searchResults->firstResult, 0);
        $this->assertEquals($searchResults->maxResultHits, 50);

        // More spesified search
        $searchResults = Elvis::search($this->sessionId, '*:*', 10, 20, '', 'metadataComplete', true);

        // Check that start and num are reflected in the response
        $this->assertEquals($searchResults->firstResult, 10);
        $this->assertEquals($searchResults->maxResultHits, 20);

        // Check metadataToReturn = 'metadataComplete' is returned in results
        $this->assertInternalType('string', $searchResults->hits[0]->metadata->metadataComplete);

        // Check that requestSecret parameter is added to the response URL's
        $this->assertContains('&requestSecret=', $searchResults->hits[0]->originalUrl);
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
        $searchResults = Elvis::search($this->sessionId, 'id:'.$this->assetId);
        $this->assertEquals($searchResults->hits[0]->metadata->gtin, 1234567890);
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
        $searchResults = Elvis::search($this->sessionId, 'id:'.$this->assetId);
        $this->assertEquals($searchResults->hits[0]->metadata->gtin, 1234567890123);
    }

    /**
    *
    * Tests for the copy and move/rename functions
    *
    * @return void
    */
    public function testCopyAndMove()
    {
        // Get assetPath of your created asset
        $searchResults = Elvis::search($this->sessionId, 'id:' . $this->assetId);

        // Check that we get one hit and the assetPath is correct type
        $this->assertEquals($searchResults->totalHits, 1);
        $this->assertInternalType('string', $searchResults->hits[0]->metadata->assetPath);
        
        // Create new filename
        $pathParts = pathinfo($searchResults->hits[0]->metadata->assetPath);
        $copyFilename = $pathParts['dirname'] . '/' . $pathParts['filename'] . '-copy.' . $pathParts['extension'];
        
        $copy = Elvis::copy($this->sessionId, $searchResults->hits[0]->metadata->assetPath, $copyFilename);
        
        // Check that we get correct processedCount
        $this->assertEquals($copy->processedCount, 1);

        // Check that the copied file exists
        $searchResults = Elvis::search($this->sessionId, 'assetPath:"' . $copyFilename . '"');
        $this->assertEquals($searchResults->totalHits, 1);

        // Rename the file
        $renamedFilename = $pathParts['dirname'] . '/' . $pathParts['filename'] . '-renamed.' . $pathParts['extension'];

        $rename = Elvis::move($this->sessionId, $copyFilename, $renamedFilename);

        // Check that we get correct processedCount
        $this->assertEquals($rename->processedCount, 1);

        // Remove the renamed filename
        $remove = Elvis::remove($this->sessionId, 'assetPath:"' . $renamedFilename . '"');
        $this->assertEquals($remove->processedCount, 1);
    }
    
    /**
    *
    * Tests for the creation of folders
    *
    * @return void
    */
    public function testCreateFolder()
    {
        // Get assetPath of your created asset
        $profile = Elvis::profile($this->sessionId);

        // Create new folder in the the User Zone aka home directory
        $newFolder = $profile->userZone . '/New folder';        
        $createFolder = Elvis::createFolder($this->sessionId, $newFolder);

        // Check that we get correct response
        $this->assertEquals($createFolder->$newFolder, 'created');

        // Try to create existing folder
        $createDuplicateFolder = Elvis::createFolder($this->sessionId, $newFolder);
        $this->assertEquals($createDuplicateFolder->$newFolder, 'already exists');

        // Remove the created folder
        $removeFolder = Elvis::remove($this->sessionId, null, null, $newFolder, false);
        
        // Since actually no assets were removed, we will get 0
        $this->assertEquals($removeFolder->processedCount, 0);
    }

    /**
    *
    * Tests for the query stats
    *
    * @return void
    */
    public function testQueryStats()
    {
        $queryStats = Elvis::queryStats($this->sessionId, 'stats_rawdata/sql/usagelog.sql', 10);
        
        // Check that we get correct response and amount
        $this->assertInternalType('array', $queryStats);
        $this->assertEquals(count($queryStats), 10);        
    }
    
    /**
    *
    * Tests or creating a relation
    *
    * @return voic
    */
    public function testCreateAndRemoveRelation()
    {
        // Create the first asset file
        $metadata = array('filename' => 'asset1.txt', 'creatorName' => 'Test user');
        $create = Elvis::create($this->sessionId, null, $metadata);
        $asset1Id = $create->id;

        // Create the second asset file
        $metadata = array('filename' => 'asset2.txt', 'creatorName' => 'Test user');
        $create = Elvis::create($this->sessionId, null, $metadata);
        $asset2Id = $create->id;

        // Create relation between these two
        $createRelation = Elvis::createRelation($this->sessionId, 'related', $asset1Id, $asset2Id);
        
        // Chech that response is in correct form
        $this->assertInternalType('object', $createRelation);

        // Search for the relation
        $searchResults = Elvis::search($this->sessionId, 'relatedTo:' . $asset1Id . ' relationTarget:child relationType:related');

        // Chech that response is in correct form
        $this->assertInternalType('object', $searchResults);
        $this->assertEquals($searchResults->totalHits, 1);

        // Check that we have relation returned in the results
        $this->assertInternalType('object', $searchResults->hits[0]->relation);
        
        // Check that the relation information is correct
        $this->assertEquals($searchResults->hits[0]->relation->relationType, 'related');
        $this->assertEquals($searchResults->hits[0]->relation->target1Id, $asset1Id);
        $this->assertEquals($searchResults->hits[0]->relation->target2Id, $asset2Id);
        $this->assertEquals($searchResults->hits[0]->relation->relationMetadata->relationModifier, Config::get('elvis::username'));
        $this->assertEquals($searchResults->hits[0]->relation->relationMetadata->relationCreator, Config::get('elvis::username'));

        // Remove the relation
        $removeRelation = Elvis::removeRelation($this->sessionId, array($searchResults->hits[0]->relation->relationId));
        $this->assertEquals($removeRelation->processedCount, 1);

        // Do the search again, we should not any hits anymore
        $searchResults = Elvis::search($this->sessionId, 'relatedTo:' . $asset1Id . ' relationTarget:child relationType:related');
        $this->assertEquals($searchResults->totalHits, 0);
    }
    
    /**
    *
    * Tests for the log usage
    *
    * @return void
    */
    public function testLogUsage()
    {
        // Set default and create unique id so we can distinguish test case from other
        $actionFound = false;
        $uniqueId = uniqid();
        
        // Create log entry
        $logUsage = Elvis::logUsage($this->sessionId, $this->assetId, 'CUSTOM_ACTION_Test', array('uniqueTestId' => $uniqueId));

        // Sleep for around 15 seconds and go through usage log to find match
        sleep(20);

        // Query usage log
        $queryStats = Elvis::queryStats($this->sessionId, 'stats_rawdata/sql/usagelog.sql');

        // Go through the stats and try to find match
        foreach($queryStats as $queryStat)
        {
            // Check if there is a match with action type
            if($queryStat->action_type == 'CUSTOM_ACTION_CUSTOM_ACTION_Test') {
                // Go through custom metadatas
                foreach($queryStat->details as $key => $value) {
                    if($key == 'uniqueTestId' && $value == $uniqueId) {
                        $actionFound = true;
                        break;
                    }
                }
            }      
        }

        $this->assertEquals($actionFound, true, "logUsage not found the usage log");        
    }

    /**
     * Tests that messages is working properly
     *
     * @return void
     */
    public function testMessages()
    {   
        // Test without any parameters
        $messages = Elvis::messages($this->sessionId);
        
        // Check that have certain known labels
        $this->assertInternalType('object', $messages);
        $this->assertEquals($messages->{'field_label.creatorEmail'}, 'E-mail');

        // Go a message query with locale fi_FI
        $messages = Elvis::messages($this->sessionId, 'fi_FI');
        $this->assertEquals($messages->{'field_label.creatorEmail'}, 'Sähköposti');
    }


}
