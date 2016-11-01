<?php

namespace Pixel\MozendaAPI;

use GuzzleHttp\Client;
use SimpleXMLElement;

class MozendaAPI {

    /**
     * Base URL
     * @var string
     */
    protected $api_endpoint = 'https://api.mozenda.com/rest';

    /**
     * Web Service Key
     *
     * @var string
     */
    private $api_key;

    /**
     * This parameter specifies the version of the API being used.
     *
     * @var string
     */
    protected $api_version = 'Mozenda10';

    /**
     * Keeps Job IDs for each agent.
     *
     * @var array
     */
    protected $job_ids;

    /**
     * Maximum number of jobs running inside one agent simultaneously.
     *
     * @var int|null
     */
    public $max_threads;


    /**
     * MozendaAPI constructor.
     *
     * @param string $api_key
     * @param string|null $api_version
     * @param string|null $api_endpoint
     */
    public function __construct($api_key, $api_version = null, $api_endpoint = null)
    {
        $this->api_key = $api_key;
        if (!empty($api_version)) $this->api_version = $api_version;
        if (!empty($api_endpoint)) $this->api_endpoint = $api_endpoint;
    }

    /**
     * Execute request to Mozenda API with specified parameters.
     *
     * @param array $params
     * @return array
     */
    private function getMozendaData($params, $return_array = true)
    {
        $params_string = '';
        if (!empty($params))
        {
            foreach ($params as $key => $param)
            {
                $params_string .= '&' . $key . '=' . $param;
            }
        }

        $api_url = $this->api_endpoint . "?WebServiceKey=" . $this->api_key . "&Service=" . $this->api_version . $params_string;
        $client = new Client();
        $res = $client->get($api_url);
        $string_xml = $res->getBody()->getContents();
        $xml = simplexml_load_string($string_xml);

        if ($return_array) $result = json_decode(json_encode($xml), true);
        else $result = $xml;

        return $result;
    }

    /*
       =============================================
                        Agent Methods
       =============================================
    */

    /**
     * Adds a new Agent. Cannot be used to overwrite an existing Agent.
     * The existing Agent must be deleted first using the Agent.Delete request.
     *
     * @param string $path_to_agent
     * @param string $agentName
     * @return array
     */
    public function addAgent($path_to_agent, $agentName)
    {
        if (!empty($path_to_agent) && !empty($agentName))
        {
            try
            {
                $agentXML = fopen($path_to_agent, 'r');

                $api_url = $this->api_endpoint . "?WebServiceKey=" . $this->api_key . "&Service=" . $this->api_version . "&Operation=Agent.Add&Name=" . $agentName;
                $client = new Client();
                $res = $client->request('POST', $api_url, [
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => $agentXML,
                            'filename' => 'AgentDefinition.xml'
                        ]
                    ],
                ]);
                $mozenda_data = $res->getBody()->getContents();
                $mozenda_data = simplexml_load_string($mozenda_data);
                $mozenda_data = json_decode(json_encode($mozenda_data), true);

                return $mozenda_data;
            }
            catch (\Exception $e) {
                return ['Error' => $e];
            }
        }

        return ['Error' => 'Please provide Path To Your Agent XML-file and Agent Name.'];
    }

    /**
     * Deletes an agent and all associated schedules for that agent.
     *
     * @param int $agentID
     */
    public function removeAgent($agentID)
    {
        $params = [
            'Operation' => 'Agent.Delete',
            'AgentID'   => $agentID
        ];
        $this->getMozendaData($params);
    }

    /**
     * Gets the Agent result collection id.
     *
     * @param int $agent_id
     * @return array|integer
     */
    public function getMozendaCollection($agent_id)
    {
        if (!empty($agent_id))
        {
            $params = [
                'Operation' => 'Agent.Get',
                'AgentID'   => $agent_id
            ];
            $mozenda_data = $this->getMozendaData($params);
            
            if($mozenda_data['Result'] == 'Success') return $mozenda_data['CollectionID'];
            else return false;
        }

        return ['Error' => 'Please provide AgentID.'];
    }

    /**
     * Returns a list of Agents and the combined collections they source.
     *
     * @param array $agents
     * @return array
     */
    public function getCombinedCollections($agents = [])
    {
        $params = [
            'Operation' => 'Agent.GetCombinedCollections',
            'AgentID'   => implode(',', $agents)
        ];
        $mozenda_data = $this->getMozendaData($params, false);
        $collections_ids = [];

        if (!empty($mozenda_data->AgentList->Agent))
        {
            foreach ($mozenda_data->AgentList->Agent as $agent)
            {
                $agent = (array)$agent;
                $collections_ids[$agent['AgentID']] = explode(',', $agent['SourcesCollections']);
            }
        }

        return $collections_ids;
    }

    /**
     * Returns a list of your agent’s jobs with detailed information.
     *
     * @param $agentID
     * @param YYYY-MM-DD|null $JobCreated
     * @param YYYY-MM-DD|null $JobStarted
     * @param YYYY-MM-DD|null $JobEnded
     * @param Active|Archived|All|null $JobState
     * @return array
     */
    public function getAgentJobs($agentID, $JobCreated=null, $JobStarted=null, $JobEnded=null, $JobState=null)
    {
        $params = [
            'Operation' => 'Agent.GetJobs',
            'AgentID'   => $agentID
        ];

        if(!empty($JobCreated)) $params['Job.Created'] = $JobCreated;
        if(!empty($JobCreated)) $params['Job.Started'] = $JobStarted;
        if(!empty($JobCreated)) $params['Job.Ended'] = $JobEnded;
        if(!empty($JobCreated)) $params['Job.State'] = $JobState;

        $mozenda_data = $this->getMozendaData($params, false);
        $jobs = [];

        if (!empty($mozenda_data->JobList->Job))
        {
            foreach ($mozenda_data->JobList->Job as $job)
            {
                $job = (array)$job;
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    /**
     * Returns a list of your agents with their ID, Name, Settings, Description, and other important information.
     *
     * @return array|null
     */
    public function getMozendaAgents()
    {
        $params = [
            'Operation' => 'Agent.GetList'
        ];
        $mozenda_data = $this->getMozendaData($params, false);
        $agent_ids = null;

        if (!empty($mozenda_data->AgentList->Agent))
        {
            $agent_ids = [];
            foreach ($mozenda_data->AgentList->Agent as $agent)
            {
                $agent = (array)$agent;
                $agent_ids[] = $agent['AgentID'];
            }
        }

        return $agent_ids;
    }

    /**
     * Starts a new job for an Agent.
     * If $total_records parameter is provided, and $this->max_threads was set up, the agent will run in a multiple threads.
     * Parameter $colllectionID is needed only if you want to specify input collection for the agent.
     *
     * @param int $agentID
     * @param int|null $total_records
     * @param int|null $colllectionID
     * @return array
     */
    public function runMozendaJob($agentID, $total_records = null, $colllectionID = null)
    {
        if (!empty($agentID))
        {
            $params = [
                'Operation' => 'Agent.Run',
                'AgentID'   => intval($agentID)
            ];

            if (!empty($colllectionID))
            {
                $viewID = $this->getMozendaView($colllectionID);
                $params['AgentParameter.ViewID'] = intval($viewID);
            }

            if (empty($total_records) || empty($this->max_threads) || $total_records < $this->max_threads)
            {
                $mozenda_data = $this->getMozendaData($params);
                if($mozenda_data['Result'] == 'Success') $this->job_ids[$agentID][] = $mozenda_data['JobID'];
                else return ['Error' => $mozenda_data];
            } else
            {
                $max_records_for_thread = ceil($total_records / $this->max_threads);

                $total = range(1, $total_records);
                $chunks_array = array_chunk($total, $max_records_for_thread);

                foreach ($chunks_array as $step)
                {
                    $start = array_shift($step);
                    $offset = $start - 1;
                    $end = end($step);
                    if ($end)
                    {
                        $params['AgentParameter.INDEX'] = $start . '-' . $end;
                        $length = $end - $offset;
                    } else
                    {
                        $params['AgentParameter.INDEX'] = $start;
                        $length = intval($max_records_for_thread);
                    }

                    $mozenda_data = $this->getMozendaData($params);
                    unset($params['AgentParameter.INDEX']);
                    if($mozenda_data['Result'] == 'Success') $this->job_ids[$agentID][] = $mozenda_data['JobID'];
                    else return ['Error' => $mozenda_data, 'Jobs' => $this->job_ids[$agentID]];
                }
            }

            return $this->job_ids[$agentID];
        }

        return ['Error' => 'Please provide AgentID.'];
    }

    /*
       =============================================
                     End Agent Methods
       =============================================
    */


    /*
       =============================================
                     Collections Methods
       =============================================
    */


    /**
     * Adds an empty collection in your account.
     *
     * @param string $name
     * @param string $description
     * @return array|int
     */
    public function createCollection($name, $description = '')
    {
        if (!empty($name))
        {
            $params = [
                'Operation'   => 'Collection.Add',
                'Name'        => $name,
                'Description' => $description
            ];
            $mozenda_data = $this->getMozendaData($params);

            if ($mozenda_data['Result'] == 'Success') return $mozenda_data['CollectionID'];
            else return $mozenda_data;
        }

        return ['Error' => 'Please provide Name for the new collection.'];
    }

    /**
     * Adds a field to the desired Collection. This field is created with the default format of “Text”.
     * This operation can only be performed on Collections that are created by a user.
     *
     * @param int $collectionID
     * @param string $field
     */
    public function addCollectionField($collectionID, $field)
    {
        $params = [
            'Operation'    => 'Collection.AddField',
            'CollectionID' => $collectionID,
            'Field'        => $field
        ];
        $this->getMozendaData($params);
    }

    /**
     * Adds an items to a collection with the values specified.
     *
     * @param int $collectionID
     * @param array $items
     * @return array
     */
    public function addCollectionItem($collectionID, $items = [])
    {
        if (!empty($collectionID) && count($items) > 0)
        {
            $params = [
                'Operation'    => 'Collection.AddItem',
                'CollectionID' => intval($collectionID)
            ];

            $api_url = $this->api_endpoint . "?WebServiceKey=" . $this->api_key . "&Service=" . $this->api_version . "&Operation=Collection.AddItem&CollectionID=" . intval($collectionID);

            $xml = new SimpleXMLElement('<ItemList/>');
            foreach ($items as $item)
            {
                $xml_item = $xml->addChild('Item');
                foreach ($item as $name => $value)
                {
                    $xml_item->addChild($name, $value);
                }
            }
            $client = new Client();
            $res = $client->request('POST', $api_url, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => $xml->asXML(),
                        'filename' => 'NewItems.xml'
                    ]
                ],
            ]);
            $mozenda_data = $res->getBody()->getContents();
            $mozenda_data = simplexml_load_string($mozenda_data);
            $mozenda_data = json_decode(json_encode($mozenda_data), true);

            return $mozenda_data;
        }

        return ['Error' => 'Please provide CollectionID and Fields.'];

    }

    /**
     * Clears the contents of a collection but leaves the collection intact.
     *
     * @param int $collectionID
     * @return array
     */
    public function clearMozendaCollection($collectionID)
    {
        if (!empty($collectionID))
        {
            $params = [
                'Operation'    => 'Collection.Clear',
                'CollectionID' => intval($collectionID)
            ];
            $mozenda_data = $this->getMozendaData($params);

            return $mozenda_data;
        }

        return ['Error' => 'Please provide CollectionID.'];
    }

    /**
     * Deletes the collection and all data within it.
     * WARNING: This operation is permanent!
     *
     * @param int $collectionID
     * @return bool
     */
    public function removeCollection($collectionID)
    {
        $params = [
            'Operation'    => 'Collection.Delete',
            'CollectionID' => $collectionID
        ];
        $mozenda_data = $this->getMozendaData($params);

        if($mozenda_data['Result'] == 'Success') return true;
        else return false;
    }

    /**
     * Deletes a field from the Collection. This operation can only be performed on Collections that are created by a user.
     *
     * @param int $collectionID
     * @param string $field
     * @return bool
     */
    public function deleteCollectionField($collectionID, $field)
    {
        $params = [
            'Operation'    => 'Collection.DeleteField',
            'CollectionID' => $collectionID,
            'Field' => $field
        ];
        $mozenda_data = $this->getMozendaData($params);

        if($mozenda_data['Result'] == 'Success') return true;
        else return false;
    }

    /**
     * Deletes an item from a collection
     *
     * @param int $collectionID
     * @param int $item_id
     * @return bool
     */
    public function deleteCollectionItem($collectionID, $item_id)
    {
        $params = [
            'Operation'    => 'Collection.DeleteItem',
            'CollectionID' => $collectionID,
            'ItemID' => $item_id
        ];
        $mozenda_data = $this->getMozendaData($params);

        if($mozenda_data['Result'] == 'Success') return true;
        else return false;
    }

    /**
     * Returns a list of fields that are in that collection with their details
     *
     * @param int $collection_id
     * @return array
     */
    public function getCollectionFields($collection_id, $include = null)
    {
        if (!empty($collection_id))
        {
            $params = [
                'Operation'    => 'Collection.GetFields',
                'CollectionID' => $collection_id
            ];

            if(!empty($include)) $params['Include'] = $include;

            $mozenda_data = $this->getMozendaData($params, false);
            $fields = [];

            if (!empty($mozenda_data->FieldList->Field))
            {
                foreach ($mozenda_data->FieldList->Field as $field)
                {
                    $field = (array)$field;
                    $fields[] = $field;
                }
            }

            return $fields;
        }

        return ['Error' => 'Please provide CollectionID.'];
    }

    /**
     * Returns a list of fields that are in that collection with their details
     *
     * @param int $collection_id
     * @return array
     */
    public function getCollectionList($collection_id)
    {
        if (!empty($collection_id))
        {
            $params = [
                'Operation'    => 'Collection.GetList',
                'CollectionID' => $collection_id
            ];

            $mozenda_data = $this->getMozendaData($params, false);
            $collections = [];

            if (!empty($mozenda_data->CollectionList->Collection))
            {
                foreach ($mozenda_data->CollectionList->Collection as $collection)
                {
                    $collection = (array)$collection;
                    $collections[] = $collection;
                }
            }

            return $collections;
        }

        return ['Error' => 'Please provide CollectionID.'];
    }

    /**
     * Returns a list of collections and their current publishing configuration.
     * Note: The PublishWhenAgentCompletes field will be removed if the collection is publishing on a schedule.
     *
     * @param array $collection_id
     * @param array $AgentID
     * @return array
     */
    public function getCollectionPublisher($collection_id = [], $AgentID = [])
    {
        if (!empty($collection_id) || !empty($AgentID))
        {
            $params = [
                'Operation'    => 'Collection.GetPublisher'
            ];

            if(!empty($collection_id)) $params['CollectionID'] = implode(',', $collection_id);
            if(!empty($AgentID)) $params['AgentID'] = implode(',', $AgentID);

            $mozenda_data = $this->getMozendaData($params, false);
            $collections = [];

            if (!empty($mozenda_data->CollectionList->Collection))
            {
                foreach ($mozenda_data->CollectionList->Collection as $collection)
                {
                    $collection = (array)$collection;
                    $collections[] = $collection;
                }
            }

            return $collections;
        }

        return ['Error' => 'Please provide CollectionID.'];
    }

    /**
     * Gets a list of views for a particular collection.
     *
     * @param int $collection_id
     * @return array
     */
    public function getMozendaView($collection_id)
    {
        if (!empty($collection_id))
        {
            $view_ids = [];

            $params = [
                'Operation'    => 'Collection.GetViews',
                'CollectionID' => $collection_id
            ];
            $mozenda_data = $this->getMozendaData($params, false);

            if (!empty($mozenda_data->ViewList->View))
            {
                $agent_ids = [];
                foreach ($mozenda_data->ViewList->View as $view)
                {
                    $view = (array)$view;
                    $view_ids[] = $view['ViewID'];
                }
            }

            return $view_ids;
        }

        return ['Error' => 'Please provide CollectionID.'];
    }

    /**
     * Publishes the collection according to the publishing information setup for the collection.
     * Currently, this can be entered via the Web Console or using the Collection.SetPublisher call.
     *
     * @param int $collectionID
     * @param null $StatusUrl
     * @return array
     */
    public function CollectionPublish($collectionID, $StatusUrl=null)
    {
        $params = [
            'Operation'    => 'Collection.Publish',
            'CollectionID' => $collectionID
        ];

        if(!empty($StatusUrl)) $params['Job.StatusUrl'] = $StatusUrl;

        $mozenda_data = $this->getMozendaData($params);

        return $mozenda_data;
    }

    /**
     * Updates or creates a new publisher for the specified collection.
     *
     * @param $collectionID
     * @param $method
     * @param $method_params
     * @return array|bool
     */
    public function CollectionSetPublisher($collectionID, $method, $method_params)
    {
        if (!empty($collection_id) && !empty($method) && !empty($method_params))
        {
            $params = [
                'Operation'    => 'Collection.SetPublisher',
                'CollectionID' => $collectionID,
                'Method'       => $method
            ];

            foreach ($method_params as $key => $value)
            {
                $params[$key] = $value;
            }

            $mozenda_data = $this->getMozendaData($params);

            if($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide CollectionID and Method with Parameters.'];
    }

    /**
     * Sets the Unique Fields on the Collection.
     * WARNING: This operation will delete any duplicates in the collection based on the new unique fields being set.
     * NOTE: Parameter $unique_fields must be a string of comma separated fields name.
     *
     * @param int $collectionID
     * @param string $unique_fields
     */
    public function removeDuplicatesFromCollection($collectionID, $unique_fields)
    {
        $params = [
            'Operation'    => 'Collection.SetUniqueFields',
            'Fields'       => $unique_fields,
            'CollectionID' => $collectionID
        ];
        $this->getMozendaData($params);
    }

    /**
     * Modifies a field in a collection.
     *
     * @param int $collectionID
     * @param int $FieldID
     * @param array $field_params
     * @return array|bool
     */
    public function updateCollectionField($collectionID, $FieldID, $field_params)
    {
        if (!empty($collection_id) && !empty($FieldID) && !empty($field_params))
        {
            $params = [
                'Operation'    => 'Collection.UpdateField',
                'CollectionID' => $collectionID,
                'FieldID'      => $FieldID
            ];

            foreach ($field_params as $key => $value)
            {
                $params[$key] = $value;
            }

            $mozenda_data = $this->getMozendaData($params);

            if ($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide CollectionID and FieldID with Field Parameters.'];
    }
    
    /**
     * Updates an item in the collection.
     * 
     * @param int $collectionID
     * @param int $ItemID
     * @param array $field_params
     * @return array|bool
     */
    public function updateCollectionItem($collectionID, $ItemID, $field_params)
    {
        if (!empty($collection_id) && !empty($ItemID) && !empty($field_params))
        {
            $params = [
                'Operation'    => 'Collection.UpdateItem',
                'CollectionID' => $collectionID,
                'ItemID'      => $ItemID
            ];

            foreach ($field_params as $key => $value)
            {
                $params[$key] = $value;
            }

            $mozenda_data = $this->getMozendaData($params);

            if ($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide CollectionID and ItemID with Field Parameters.'];
    }

    /*
       =============================================
                   End Collection Methods
       =============================================
    */
    
    /*
       =============================================
                        Job Methods
       =============================================
    */

    /**
     * Cancels a Job in the system.
     * Note: A job must be in a Paused or Error State to cancel a job
     *
     * @param int $jobID
     * @return array|bool
     */
    public function cancelJob($jobID)
    {
        if (!empty($jobID))
        {
            $params = [
                'Operation' => 'Job.Cancel',
                'JobID'     => $jobID
            ];
            $mozenda_data = $this->getMozendaData($params);

            if($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide JobID.'];
    }

    /**
     * Gets the details of a job by the Job ID.
     *
     * @param int $jobID
     * @return array
     */
    public function getJob($jobID)
    {
        if (!empty($jobID))
        {
            $params = [
                'Operation' => 'Job.Get',
                'JobID'     => $jobID
            ];
            $mozenda_data = $this->getMozendaData($params);

            return $mozenda_data;
        }

        return ['Error' => 'Please provide JobID.'];
    }

    /**
     * Gets the progress of the Agent.
     *
     * @param int $JobID
     * @return array
     */
    public function getAgentProgress($JobID)
    {
        if (!empty($viewID))
        {
            $params = [
                'Operation' => 'Job.GetAgentProgress',
                'JobID'    => $JobID
            ];
            $mozenda_data = $this->getMozendaData($params, false);
            $items = [];
            foreach ($mozenda_data->BeginListList->BeginList as $item)
            {
                $item = json_decode(json_encode($item), true);
                $items[] = $item;
            }

            return $items;
        }

        return ['Error' => 'Please provide JobID.'];
    }


    /**
     * Gets all the active jobs for the account.
     *
     * @param YYYY-MM-DD|null $JobCreated
     * @param YYYY-MM-DD|null $JobStarted
     * @param YYYY-MM-DD|null $JobEnded
     * @param Active|Archived|All|null $JobState
     * @return array
     */
    public function getJobList($JobCreated=null, $JobStarted=null, $JobEnded=null, $JobState=null)
    {
        $params = [
            'Operation' => 'Job.GetList'
        ];

        if(!empty($JobCreated)) $params['Job.Created'] = $JobCreated;
        if(!empty($JobCreated)) $params['Job.Started'] = $JobStarted;
        if(!empty($JobCreated)) $params['Job.Ended'] = $JobEnded;
        if(!empty($JobCreated)) $params['Job.State'] = $JobState;

        $mozenda_data = $this->getMozendaData($params, false);
        $jobs = [];

        if (!empty($mozenda_data->JobList->Job))
        {
            foreach ($mozenda_data->JobList->Job as $job)
            {
                $job = (array)$job;
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    /**
     * Issues the ‘Pause’ command for a job currently running in the system.
     *
     * @param int $jobID
     * @return array|bool
     */
    public function pauseJob($jobID)
    {
        if (!empty($jobID))
        {
            $params = [
                'Operation' => 'Job.Pause',
                'JobID'     => $jobID
            ];
            $mozenda_data = $this->getMozendaData($params);

            if($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide JobID.'];
    }

    /**
     * Resumes a job that is in a Paused or Error state.
     *
     * @param int $jobID
     * @return array|bool
     */
    public function resumeJob($jobID)
    {
        if (!empty($jobID))
        {
            $params = [
                'Operation' => 'Job.Resume',
                'JobID'     => $jobID
            ];
            $mozenda_data = $this->getMozendaData($params);

            if($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide JobID.'];
    }

    /*
       =============================================
                       End Job Methods
       =============================================
    */


    /*
       =============================================
                        View Methods
       =============================================
    */


    /**
     * Deletes all the items in the View.
     *
     * @param int $viewID
     * @return array|bool
     */
    public function deleteViewItems($viewID)
    {
        if (!empty($viewID))
        {
            $params = [
                'Operation' => 'View.DeleteItems',
                'ViewID'    => $viewID
            ];
            $mozenda_data = $this->getMozendaData($params);

            if($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide ViewID.'];
    }

    /**
     * Returns items from a view.
     *
     * @param int $viewID
     * @return array
     */
    public function getViewItems($viewID)
    {
        if (!empty($viewID))
        {
            $params = [
                'Operation' => 'View.GetItems',
                'ViewID'    => $viewID
            ];
            $mozenda_data = $this->getMozendaData($params);
            $items = [];
            if (!empty($mozenda_data->ItemList->Item))
            {
                foreach ($mozenda_data->ItemList->Item as $item)
                {
                    $item = json_decode(json_encode($item), true);
                    $items[] = $item;
                }
            }

            return $items;
        }

        return ['Error' => 'Please provide ViewID.'];
    }


    /**
     * Sets the fields that are included in the view and also their order.
     *
     * @param int $viewID
     * @param array $fields
     * @return array|bool
     */
    public function setViewFields($viewID, $fields = [])
    {
        if (!empty($viewID) && !empty($fields))
        {
            $params = [
                'Operation' => 'View.SetFields',
                'ViewID'    => $viewID,
                'Fields'    => implode(',', $fields)
            ];
            $mozenda_data = $this->getMozendaData($params);

            if($mozenda_data['Result'] == 'Success') return true;
            else return false;
        }

        return ['Error' => 'Please provide ViewID and Fields.'];
    }


    /*
       =============================================
                      End View Methods
       =============================================
    */


    /**
     * Checks if all jobs are done inside the defined agent.
     *
     * @param int $agentID
     * @return array|bool
     */
    public function isAllAgentJobsDone($agentID)
    {
        if (!empty($agentID))
        {
            $jobs_id = $this->job_ids[$agentID];
            if (empty($jobs_id))
                return ['Error' => 'There are no jobs for AgentID: ' . $agentID];
            foreach ($jobs_id as $job_id)
            {
                $params = [
                    'Operation' => 'Job.Get',
                    'JobID'     => $job_id
                ];
                $mozenda_data = $this->getMozendaData($params);
                if (empty($mozenda_data['Job']))
                    return false;
                if ($mozenda_data['Job']['Status'] != 'Done')
                {
                    return false;
                }
            }

            return true;
        }

        return ['Error' => 'Please provide AgentID.'];
    }

    /**
     * Gets result data from the defined agent.
     * You should specify $unique_fields parameter if you want to remove duplicated results.
     * NOTE: Parameter $unique_fields must be a string of comma separated names of fields.
     *
     * @param int $agentID
     * @param string|null $unique_fields
     * @return array
     */
    public function collectData($agentID, $unique_fields = null)
    {
        if (!empty($agentID))
        {
            if($collection_id = $this->getMozendaCollection($agentID))
            {
                $items = [];

                if (!empty($unique_fields)) $this->removeDuplicatesFromCollection($collection_id, $unique_fields);
                $temp = [];
                $views = $this->getMozendaView($collection_id);
                foreach ($views as $view_id)
                {
                    $temp = $this->getViewItems($view_id);
                    if (!empty($items))
                    {
                        $items = array_merge_recursive($items, $temp);
                    } else $items = $temp;
                }

                return $items;
            }
        }

        return ['Error' => 'Please provide AgentID.'];
    }
}
