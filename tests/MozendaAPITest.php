<?php

use Pixel\MozendaAPI\MozendaAPI;

class MozendaAPITest extends PHPUnit_Framework_TestCase {
    
    private $mozenda;

    public function __construct()
    {
        $this->mozenda = new MozendaAPI('XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX');
    }

    public function test_addAgent()
    {
        $agent = $this->mozenda->addAgent('path/to/agent.xml', 'My Agent');
    }

    public function test_removeAgent()
    {
        $this->mozenda->removeAgent(1001);
    }

    public function test_getMozendaCollection()
    {
        $collection_id = $this->mozenda->getMozendaCollection(1002);
    }
    
    public function test_getCombinedCollections()
    {
        $collections_ids = $this->mozenda->getCombinedCollections([1002]);
    }
    
    public function test_getAgentJobs()
    {
        $jobs = $this->mozenda->getAgentJobs(1002);
    }

    public function test_getMozendaAgents()
    {
        $agentss = $this->mozenda->getMozendaAgents();
    }

    public function test_runMozendaJob()
    {
        $jobs = $this->mozenda->runMozendaJob(1002);
    }




    public function test_createCollection()
    {
        $collectionID = $this->mozenda->createCollection('My Collection');
    }
    
    public function test_addCollectionField()
    {
        $this->mozenda->addCollectionField(1002, 'My Field');
    }
    
    public function test_addCollectionItem()
    {
        $items = [
            [
                'Address' => '1 Main Street',
                'ZIP' => '90210'
            ],
            [
                'Address' => '2 Main Street',
                'ZIP' => '78001'
            ]
        ];
        
        $this->mozenda->addCollectionItem(1002, $items);
    }

    public function test_clearMozendaCollection()
    {
        $this->mozenda->clearMozendaCollection(1002);
    }

    public function test_removeCollection()
    {
        $this->mozenda->removeCollection(1002);
    }

    public function test_deleteCollectionField()
    {
        $this->mozenda->deleteCollectionField(1002, 'Field Name');
    }

    public function test_deleteCollectionItem()
    {
        $this->mozenda->deleteCollectionItem(1002, 1001);
    }

    public function test_getCollectionFields()
    {
        $fields = $this->mozenda->getCollectionFields(1002);
    }

    public function test_getCollectionList()
    {
        $collections = $this->mozenda->getCollectionList(1002);
    }

    public function test_getCollectionPublisher()
    {
        $collections = $this->mozenda->getCollectionPublisher([1001,1002]);
    }

    public function test_getMozendaView()
    {
        $view_ids = $this->mozenda->getMozendaView(1002);
    }

    public function test_CollectionPublish()
    {
        $result = $this->mozenda->CollectionPublish(1002);
    }

    public function test_CollectionSetPublisher()
    {
        $this->mozenda->CollectionSetPublisher(1002, 'Email', ['EmailAddress' => 'test@mail.com']);
    }

    public function test_removeDuplicatesFromCollection()
    {
        $this->mozenda->removeDuplicatesFromCollection(1002, 'Field1,Field2,Field3');
    }

    public function test_updateCollectionField()
    {
        $this->mozenda->updateCollectionField(1002, 1005, ['Name' => 'MyNewFieldName', 'Description' => 'My new field description', 'Format' => 'File']);
    }

    public function test_updateCollectionItem()
    {
        $this->mozenda->updateCollectionItem(1002, 1005, ['Field.FirstName' => 'Harry', 'Field.LastName' => 'Johnson', 'Field.Age' => '27']);
    }





    public function test_cancelJob()
    {
        $this->mozenda->cancelJob('EE64EC0E-F233-4549-8DF0-852298965F81');
    }
    
    public function test_getJob()
    {
        $this->mozenda->getJob('EE64EC0E-F233-4549-8DF0-852298965F81');
    }
    
    public function test_getAgentProgress()
    {
        $items = $this->mozenda->getAgentProgress('EE64EC0E-F233-4549-8DF0-852298965F81');
    }

    public function test_getJobList()
    {
        $jobs = $this->mozenda->getJobList();
    }

    public function test_pauseJob()
    {
        $jobs = $this->mozenda->pauseJob('EE64EC0E-F233-4549-8DF0-852298965F81');
    }

    public function test_resumeJob()
    {
        $this->mozenda->resumeJob('EE64EC0E-F233-4549-8DF0-852298965F81');
    }
    
    
    

    public function test_deleteViewItems()
    {
        $this->mozenda->deleteViewItems(1002);
    }
    
    public function test_getViewItems()
    {
        $items = $this->mozenda->getViewItems(1002);
    }

    public function test_setViewFields()
    {
        $this->mozenda->setViewFields(1002, ['Field1,Field2']);
    }

    
    
    
    public function test_isAllAgentJobsDone()
    {
        $this->mozenda->isAllAgentJobsDone(1002);
    }
    
    public function test_collectData()
    {
        $this->mozenda->collectData(1002);
    }


}