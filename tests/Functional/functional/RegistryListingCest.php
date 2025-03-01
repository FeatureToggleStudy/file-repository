<?php declare(strict_types=1);

namespace Tests\Functional;

use FunctionalTester;

class RegistryListingCest
{
    private const SAMPLE_FILE = 'https://sample-videos.com/img/Sample-jpg-image-50kb.jpg';

    private function populateWithSomeData(FunctionalTester $I): void
    {
        $I->haveRoles(['upload.all']);
        $I->uploadByPayload('Hello world.', ['fileName' => 'hello.txt', 'public' => true]);
        $I->uploadByPayload('Hello workers, to you the world belong.',
            ['fileName' => 'iwa.txt', 'public' => true, 'password' => 'IWA-AIT.org']
        );
    }

    public function testListingAllFiles(FunctionalTester $I): void
    {
        $this->populateWithSomeData($I);
        $I->haveRoles(['view.files_from_all_tags', 'view.can_use_listing_endpoint_at_all']);

        $I->listFiles(['page' => 1, 'limit' => 20]);
        $I->canSeeResponseContains('hello.txt');
    }

    public function testFindByName(FunctionalTester $I): void
    {
        $this->populateWithSomeData($I);
        $I->haveRoles(['view.files_from_all_tags', 'view.can_use_listing_endpoint_at_all']);

        // case 1: will find, because "hello.txt" was uploaded
        $I->listFiles(['page' => 1, 'limit' => 20, 'searchQuery' => 'hello.txt']);
        $I->canSeeResponseContains('hello.txt');

        // case 2: will not find, as the file with similar name was not uploaded yet
        $I->listFiles(['page' => 1, 'limit' => 20, 'searchQuery' => 'non-existing.txt']);
        $I->cantSeeResponseContains('non-existing.txt');
    }

    public function testSearchingWithoutPassword(FunctionalTester $I): void
    {
        $this->populateWithSomeData($I);
        $I->haveRoles(['view.files_from_all_tags', 'view.can_use_listing_endpoint_at_all']);

        // case 1: Without a valid password the file is anonymous
        $I->listFiles(['page' => 1, 'limit' => 1, 'searchQuery' => 'iwa.txt']);
        $I->cantSeeResponseContains('iwa.txt');
        $I->canSeeResponseContains('anonymous');

        // case 2: With a valid password the file is shown
        $I->listFiles(['page' => 1, 'limit' => 1, 'searchQuery' => 'iwa.txt', 'password' => 'IWA-AIT.org']);
        $I->canSeeResponseContains('iwa.txt');
    }
}
