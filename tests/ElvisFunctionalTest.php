<?php namespace LasseLehtinen\Elvis\Tests;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Config;
use LasseLehtinen\Elvis\Facades\Elvis;

class ElvisFunctionalTest extends \Orchestra\Testbench\TestCase
{
    protected $sessionId;
    protected $assetId;

    // Override package service provider and alias
    protected function getPackageProviders($app)
    {
        return array('LasseLehtinen\Elvis\ElvisServiceProvider');
    }

    protected function getPackageAliases($app)
    {
        return array('Elvis' => 'LasseLehtinen\Elvis\Facades\Elvis');
    }

    public function setUp(): void
    {
        parent::setUp();

        // Load Dotenv
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        // Set Laravel configuration parameters
        Config::set('elvis.api_endpoint_uri', $_ENV['ELVIS_API_ENDPOINT_URI']);
        Config::set('elvis.username', $_ENV['ELVIS_USERNAME']);
        Config::set('elvis.password', $_ENV['ELVIS_PASSWORD']);

        // Get session Id to user in the queries
        $this->token = Elvis::login();

        // Upload a randon file to Elvis for various tests
        $temporaryFilename = tempnam("/tmp", "ElvisTest");

        // Create a file and store asset id
        $create = Elvis::create($this->token, $temporaryFilename, ['folderPath' => '/Werner Söderström Osakeyhtiö/Testaus']);
        $this->assetId = $create->id;

        // Remove temporary file
        unlink($temporaryFilename);
    }

    public function tearDown(): void
    {
        // Remove asset from Elvis
        $remove = Elvis::remove($this->token, null, array($this->assetId), null, false);

        // Log out from Elvis
        $logout = Elvis::logout($this->token);
    }

    /**
     * Tests that basic login is succesfull and session id is returned
     *
     * @return void
     */
    public function testLogin()
    {
        // Test that login is succesful and we get token
        $this->assertIsString($this->token);
    }

    /**
     * Tests that basic login with incorrect username and password returns correct error
     *
     * @return void
     */
    public function testLoginWithIncorrectUsernameAndPassword()
    {
        // Set incorrect username and password
        Config::set('elvis.username', 'incorrect_username');
        Config::set('elvis.password', 'incorrect_password');

        // Try login
        $token = Elvis::login();

        $this->assertEquals($token, null);
    }

    /**
     * Tests that basic login is succesfull and session id is returned
     *
     * @return void
     */
    public function testProfile()
    {
        // Fetch profile
        $profile = Elvis::profile($this->token);

        // Chech that profile is in correct form
        $this->assertIsObject($profile);

        // Check that we have authorieties and user groups
        $this->assertIsArray($profile->authorities);
        $this->assertIsArray($profile->groups);

        // Check that username is the same we used to log in
        $this->assertEquals($profile->username, Config::get('elvis.username'));
    }

    /**
     * Tests that creating a file and assigning metadata to it works fine
     * @group regresssion
     * @return void
     */
    public function testCreate()
    {
        // Create temporary file
        $temporaryFilename = tempnam("/tmp", "ElvisTest");

        // Create additional metadata
        $metadata = array('gtin' => '123456', 'creatorName' => 'Test', 'folderPath' => '/Werner Söderström Osakeyhtiö/Testaus');

        // Create a file
        $create = Elvis::create($this->token, $temporaryFilename, $metadata);

        // Chech that response is in correct form
        $this->assertIsObject($create);

        // Check that the given metadata is set on the object
        $this->assertEquals($create->metadata->gtin, '123456');
        $this->assertIsArray($create->metadata->creatorName);
        $this->assertEquals('Test', $create->metadata->creatorName[0]);
        $this->assertEquals('/Werner Söderström Osakeyhtiö/Testaus', $create->metadata->folderPath);
    }

    /**
     * Tests that search functions works with different parameters
     *
     * @return void
     */
    public function testSearch()
    {
        // General search without any parameters
        $searchResults = Elvis::search($this->token, '*:*');

        // Check that defaults are OK (start = 0 and num = 50)
        $this->assertEquals($searchResults->firstResult, 0);
        $this->assertEquals($searchResults->maxResultHits, 50);

        // More spesified search
        $searchResults = Elvis::search($this->token, '*:*', 10, 20, '', 'metadataComplete', true);

        // Check that start and num are reflected in the response
        $this->assertEquals($searchResults->firstResult, 10);
        $this->assertEquals($searchResults->maxResultHits, 20);

        // Check metadataToReturn = 'metadataComplete' is returned in results
        $this->assertIsString($searchResults->hits[0]->metadata->metadataComplete);

        // Check that requestSecret parameter is added to the response URL's
        $this->assertStringContainsString('requestSecret=', $searchResults->hits[0]->originalUrl);
    }

    /**
     * Tests that browse function returns results
     *
     * @return void
     */
    public function testBrowse()
    {
        // General search without any parameters
        $browse_results = Elvis::browse($this->token, '.');
        $this->assertIsArray($browse_results);
    }

    /**
     * Tests for metadata update function
     *
     * @return void
     */
    public function testUpdate()
    {
        // Update one metadata field
        $update = Elvis::update($this->token, $this->assetId, null, ['gtin' => '1234567890']);

        // Check we that get empty object as return
        $this->assertIsObject($update);

        // Get the asset info again and check that the metadata is updated
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);
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
        $updatebulk = Elvis::updatebulk($this->token, 'id:' . $this->assetId, ['gtin' => '1234567890123'], false);

        // Check that we processedCount 1
        $this->assertEquals($updatebulk->processedCount, 1);

        // Refetch asset and check that metadata is updated
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);
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
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);

        // Check that we get one hit and the assetPath is correct type
        $this->assertEquals($searchResults->totalHits, 1);
        $this->assertIsString($searchResults->hits[0]->metadata->assetPath);

        // Create new filename
        $pathParts = pathinfo($searchResults->hits[0]->metadata->assetPath);
        $copyFilename = $pathParts['dirname'] . '/' . $pathParts['filename'] . '-copy';

        $copy = Elvis::copy($this->token, $searchResults->hits[0]->metadata->assetPath, $copyFilename);

        // Check that we get correct processedCount
        $this->assertEquals($copy->processedCount, 1);

        // Check that the copied file exists
        $searchResults = Elvis::search($this->token, 'assetPath:"' . $copyFilename . '"');
        $this->assertEquals($searchResults->totalHits, 1);

        // Rename the file
        $renamedFilename = $pathParts['dirname'] . '/' . $pathParts['filename'] . '-renamed';

        $rename = Elvis::move($this->token, $copyFilename, $renamedFilename);

        // Check that we get correct processedCount
        $this->assertEquals($rename->processedCount, 1);

        // Remove the renamed filename
        $remove = Elvis::remove($this->token, 'assetPath:"' . $renamedFilename . '"');
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
        $profile = Elvis::profile($this->token);

        // Remove and create new folder
        $newFolder = '/Werner Söderström Osakeyhtiö/Testaus/New folder';
        $removeFolder = Elvis::remove($this->token, null, null, $newFolder, false);
        $createFolder = Elvis::createFolder($this->token, $newFolder);

        // Check that we get correct response
        $this->assertEquals($createFolder->$newFolder, 'created');

        // Check that we get one folder
        $browse = Elvis::browse($this->token, '/Werner Söderström Osakeyhtiö/Testaus/');
        $this->assertCount(1, $browse);

        // Try to create existing folder
        $createDuplicateFolder = Elvis::createFolder($this->token, $newFolder);
        $this->assertEquals($createDuplicateFolder->$newFolder, 'already exists');

        // Remove the created folder
        $removeFolder = Elvis::remove($this->token, null, null, $newFolder, 'false');

        // Browse should return empty array now
        $browse = Elvis::browse($this->token, '/Werner Söderström Osakeyhtiö/Testaus/');
        $this->assertEmpty($browse);
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
        $asset1Filename = tempnam("/tmp", "ElvisTest");
        $metadata = array('creatorName' => 'Test user', 'folderPath' => '/Werner Söderström Osakeyhtiö/Testaus');
        $create = Elvis::create($this->token, $asset1Filename, $metadata);
        $asset1Id = $create->id;
        unlink($asset1Filename);

        // Create the second asset file
        $asset2Filename = tempnam("/tmp", "ElvisTest");
        $metadata = array('creatorName' => 'Test user', 'folderPath' => '/Werner Söderström Osakeyhtiö/Testaus');
        $create = Elvis::create($this->token, $asset2Filename, $metadata);
        $asset2Id = $create->id;
        unlink($asset2Filename);

        // Create relation between these two
        $createRelation = Elvis::createRelation($this->token, 'related', $asset1Id, $asset2Id);

        // Chech that response is in correct form
        $this->assertIsObject($createRelation);

        // Search for the relation
        $searchResults = Elvis::search($this->token, 'relatedTo:' . $asset1Id . ' relationTarget:child relationType:related');

        // Chech that response is in correct form
        $this->assertIsObject($searchResults);
        $this->assertEquals($searchResults->totalHits, 1);

        // Check that we have relation returned in the results
        $this->assertIsObject($searchResults->hits[0]->relation);

        // Check that the relation information is correct
        $this->assertEquals($searchResults->hits[0]->relation->relationType, 'related');
        $this->assertEquals($searchResults->hits[0]->relation->target1Id, $asset1Id);
        $this->assertEquals($searchResults->hits[0]->relation->target2Id, $asset2Id);
        $this->assertEquals($searchResults->hits[0]->relation->relationMetadata->relationModifier, Config::get('elvis.username'));
        $this->assertEquals($searchResults->hits[0]->relation->relationMetadata->relationCreator, Config::get('elvis.username'));

        // Remove the relation
        $removeRelation = Elvis::removeRelation($this->token, array($searchResults->hits[0]->relation->relationId));
        $this->assertEquals($removeRelation->processedCount, 1);

        // Do the search again, we should not any hits anymore
        $searchResults = Elvis::search($this->token, 'relatedTo:' . $asset1Id . ' relationTarget:child relationType:related');
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
        $logUsage = Elvis::logUsage($this->token, $this->assetId, 'CUSTOM_ACTION_Test', array('uniqueTestId' => $uniqueId));

        // Should return empty response
        $this->assertInstanceOf('stdClass', $logUsage);
        $this->assertCount(0, get_object_vars($logUsage));
    }

    /**
     * Tests that messages is working properly
     *
     * @return void
     */
    public function testMessages()
    {
        // Test without any parameters
        $messages = Elvis::messages($this->token);

        // Check that have certain known labels
        $this->assertIsObject($messages);
        $this->assertEquals($messages->{'field_label.creatorEmail'}, 'E-mail');

        // Do a messages query with locale fi_FI
        $messages = Elvis::messages($this->token, 'fi_FI');
        $this->assertEquals($messages->{'field_label.creatorEmail'}, 'Sähköposti');
    }

    /**
     * Tests that no new messages does not return exception because a 304 HTTP response is returned
     *
     * @return void
     */
    public function testNoNewMessages()
    {
        // Do a messages query with locale fi_FI with Epoch timestamp in the far future (12/31/9999)
        $messages = Elvis::messages($this->token, 'fi_FI', 1893456000000);
        $this->assertNull($messages);
    }

    /**
     * Test for the Zip function
     *
     * @return void
     */
    public function testZipDownload()
    {
        // Test without any parameters
        $zipDownload = Elvis::zip($this->token, 'test.zip', 'original', array($this->assetId));

        $this->assertIsString($zipDownload->fileName);
        $this->assertEquals(200, $zipDownload->statusCode);
    }

    /**
     * Test adding and removing metadata values with + and -
     *
     * @return void
     */
    public function testAddValuesToExistingMetadata()
    {
        // Update one metadata field
        $update = Elvis::update($this->token, $this->assetId, null, ['tags' => 'beach, house']);

        // Get the asset info again and check that the metadata is updated
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);

        // Check that we have two tags
        $this->assertEquals(2, count($searchResults->hits[0]->metadata->tags));
        $this->assertEquals($searchResults->hits[0]->metadata->tags[0], 'beach');
        $this->assertEquals($searchResults->hits[0]->metadata->tags[1], 'house');

        // Remove the values and replace them
        $update = Elvis::update($this->token, $this->assetId, null, ['tags' => '-beach, -house, +villa, +sunny']);

        // Check we that get empty object as return
        $this->assertIsObject($update);

        // Get the asset info again and check that the metadata is updated
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);

        // Check that we have two tags
        $this->assertEquals(2, count($searchResults->hits[0]->metadata->tags));
        $this->assertEquals($searchResults->hits[0]->metadata->tags[0], 'sunny');
        $this->assertEquals($searchResults->hits[0]->metadata->tags[1], 'villa');
    }

    /**
     * Test for checkout and undocheckout
     *
     * @return void
     */
    public function testCheckoutAndUndocheckout()
    {
        // Checkout the asset
        $checkout = Elvis::checkout($this->token, $this->assetId);

        // Get the asset info again and check that the checkout flag is updated
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);
        $this->assertEquals($searchResults->hits[0]->metadata->checkedOutBy, Config::get('elvis.username'));
        $this->assertIsObject($searchResults->hits[0]->metadata->checkedOut);

        // Undo the asset
        $undocheckout = Elvis::undocheckout($this->token, $this->assetId);
        $searchResults = Elvis::search($this->token, 'id:' . $this->assetId);

        // Check that the checkedOutBy and checkedOut is not set anymore
        $this->assertEquals(isset($searchResults->hits[0]->metadata->checkedOutBy), false);
        $this->assertEquals(isset($searchResults->hits[0]->metadata->checkedOut), false);
    }

    /**
     * Test Authkey creation
     *
     * @return void
     */
    public function testCreateUpdateAndRevokeAuthkey()
    {
        // Checkout the asset
        $createAuthKey = Elvis::createAuthKey($this->token, 'Test', '2999-01-01', array($this->assetId));

        // Check that response is correct type
        $this->assertIsObject($createAuthKey);

        // Check that we get authKey
        $this->assertIsString($createAuthKey->authKey);

        // Checkout the asset
        $updateAuthKey = Elvis::updateAuthKey($this->token, $createAuthKey->authKey, 'Test', '2999-02-02');

        // Revoke the auth key
        $revokeAuthKeys = Elvis::revokeAuthKeys($this->token, array($createAuthKey->authKey));

        $this->assertIsObject($createAuthKey);
    }
}
