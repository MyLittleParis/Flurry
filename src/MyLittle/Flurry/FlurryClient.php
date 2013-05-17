<?php
namespace MyLittle\Flurry;
use Exception;

/**
 * Flurry Class
 * 
 * This source file can be used to communicate with Flurry (www.flurry.com)
 * You need to enable the API access in your flurry account before using this class
 * ( Class uses JSON )
 * The rate limit for the API is 1 request per second. Hence "sleep(1)"
 * 
 * @author          Ekaterina Johnston <ekaterina.johnston@gmail.com>
 * @version         1.0.2 (2013-05-17)
 * @example         $fl = new FlurryClient($apiAccessCode, $apiKey, 5);
 *                  $app_metrics = $fl->getAllAppMetrics("2013-05-14");
 */
class FlurryClient
{
    /**
     * List of Fluffy APIs
     * @var array
     */
    private $apis = array('appMetrics', 'appInfo', 'eventMetrics');

    /**
     * List of metrics for "appMetrics" API
     * @var array
     */
    private $appMetrics = array(
                    "ActiveUsers",
                    "ActiveUsersByWeek",
                    "ActiveUsersByMonth",
                    "NewUsers",
                    "MedianSessionLength",
                    "AvgSessionLength",
                    "Sessions",
                    "RetainedUsers",
                    "PageViews",
                    "AvgPageViewsPerSession"
                );
    /**
     * List of metrics for "appInfo" API
     * @var array
     */
    private $appInfo = array(
                    "getApplication",
                    "getAllApplications"
                );

    /**
     * List of metrics for "eventMetrics" API
     * @var array
     */
    private $eventMetrics = array(
                    "Summary",
                    "Event" 
                );
    
    /**
     * Flurry Api Access Code and Api Key (https://dev.flurry.com/)
     * API access needs to be enabled before using this class
     */
    private $apiAccessCode;
    private $apiKey;
    
    /**
     * Maximum number of retries on flurry error
     * Equals 3 by default
     * @var integer 
     */
    private $max_try_cnt;

    /**
     * Default Constructor
     * @param type $apiAccessCode
     * @param type $apiKey
     * @return type
     */
    public function __construct($apiAccessCode, $apiKey, $max_try_cnt = 3)
    {
        $this->apiAccessCode = $apiAccessCode;
        $this->apiKey = $apiKey;
        $this->max_try_cnt = $max_try_cnt;
    }
    
    /**
     * Resets the ApiKey
     * @param type $apiAccessCode
     * @param type $apiKey
     */
    public function connectToApi($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Gets the apiKey
     * @return string
     */
    private function getApiKey()
    {
        return (string) $this->apiKey;
    }

    /**
     * Gets the apiAccessCode
     * @return string
     */
    private function getApiAccessCode()
    {
        return (string) $this->apiAccessCode;
    }
    
    /**
     * Makes Flurry Calls
     * @param string $api           Name of APi to use
     * @param string $metric_name   Name of metric to use
     * @param string $startDate     YYYY-MM-DD format
     * @param string $endDate       YYYY-MM-DD format
     * @param string $eventName     Name of the Event to use (only applicable for )
     * @param string $country       Specifying a value of "ALL" or "all" for the COUNTRY will return a result which is broken down by countries
     * @param string $versionName   Name set by the developer for each version of the application. This can be found by logging into the Flurry website or contacting support.
     * @param string $groupBy       Changes the grouping of data into DAYS, WEEKS, or MONTHS. All metrics default to DAYS (except ActiveUsersByWeek and ActiveUsersByMonth)
     * 
     * @example $this->call('appMetrics', 'activeUsers', '2013-04-19', null, null, null, null, null)
     */
    private function call($api, $metric_name, $startDate, $endDate, $eventName, $country, $versionName, $groupBy)
    {
        // Formatting the date
        if (null == $endDate)
            $endDate = $startDate;
        if ((null != $endDate)&&(!is_string($startDate)))
            $startDate = $this->convertDateToString($startDate);
        if ((null != $endDate)&&(!is_string($endDate)))
            $endDate = $this->convertDateToString($endDate);
        
        //Configures parameters
        $parameters = array(
            // One for each Flurry account
            'apiAccessCode' =>$this->getApiAccessCode(), 
            // One for each application
            'apiKey' => $this->getApiKey(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'eventName' => $eventName,
            'country' => $country,
            'versionName' => $versionName,
            'groupBy' => $groupBy
            );

        //Generates the URL
        $url = "http://api.flurry.com/".$api."/".$metric_name."?".http_build_query($parameters);
        
        $config = array(
                'http' => array(
                    'header' => 'Accept: application/json',
                    'method' => 'GET',
                    'ignore_errors' =>  true,
                )
            );
        $stream = stream_context_create($config);
        
        // getContents() might throw an error
        $result = $this->getContents($url, $stream);
        sleep(1);
        return $result; 
    }

    /**
     * Tries to get file contents and json decodes it
     * @param integer $try_cnt Number of the try
     */
    private function getContents($url, $stream, $try_cnt = 0) {
        sleep(1);
        $try_cnt++;
        $contents = file_get_contents($url, false, $stream);
        $result = json_decode($contents);
        // Upon error
        if (isset($result->code)) {
            //throw new \Exception($result->code." - '".$result->message."'", $result->code);
            if ($try_cnt <= $this->max_try_cnt) {
                $result = $this->getContents($url, $stream, $try_cnt);
            } else {
                throw new \Exception($result->code." - '".$result->message."'", $result->code);
            }
        } else {
            return $result;
        }
    }

    /**
     * Convers a DateTime object to YYYY-MM-DD formatted string
     * @param datetime $datetimeobject
     * @return string  $stringdate
     */
    private function convertDateToString($datetimeobject) {
        $stringdate = date_format($datetimeobject , "Y-m-d");
        return $stringdate;
    }
    
    /**
     * Helper function. Recursively converts an object to array
     * @param object $obj
     * @return array $arr
     */
    public function convertObjectToArray($obj) 
    {
        $arr = array();
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($arrObj as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->convertObjectToArray($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }
    
    /////////////////////
    ////  API Functions
    /////////////////////
    
    //////////  "appMetrics" API functions
    // Parameter order : ($startDate, $endDate, $country, $versionName, $groupBy)
    // No 'groupBy' parameter for 'getActiveUsersXXX' functions
    
    /**
     *  Total number of unique users who accessed the application per day
     */
    public function getActiveUsers($startDate, $endDate=null, $country=null, $versionName=null) {
        return $this->call('appMetrics', 'ActiveUsers', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy = null);
    }
    
    /**
     * Total number of unique users who accessed the application per week
     * Only returns data for dates which specify at least a complete calendar week
     * (Can't use 'groupBy' parameter. The data is grouped by WEEKS)
     */
    public function getActiveUsersByWeek($startDate, $endDate=null, $country=null, $versionName=null) {
        return $this->call('appMetrics', 'ActiveUsersByWeek', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy = null);
    }
    
    /**
     * Total number of unique users who accessed the application per month
     * Only returns data for dates which specify at least a complete calendar month
     * (Can't use 'groupBy' parameter. The data is grouped by MONTHS)
     */
    public function getActiveUsersByMonth($startDate, $endDate=null, $country=null, $versionName=null) {
        return $this->call('appMetrics', 'ActiveUsersByMonth', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy = null);
    }
    
    /**
     * Total number of unique users who used the application for the first time per day
     */
    public function getNewUsers($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'NewUsers', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Median length of a user session per day
     */
    public function getMedianSessionLength($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'MedianSessionLength', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Average length of a user session per day
     */
    public function getAvgSessionLength($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'AvgSessionLength', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * The total number of times users accessed the application per day
     */
    public function getSessions($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'Sessions', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Total number of users who remain active users of the application per day
     */
    public function getRetainedUsers($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'RetainedUsers', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Total number of page views per day
     */
    public function getPageViews($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'PageViews', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
    
    /**
     * Average page views per session for each day
     */
    public function getAvgPageViewsPerSession($startDate, $endDate=null, $country=null, $versionName=null, $groupBy=null) {
        return $this->call('appMetrics', 'AvgPageViewsPerSession', $startDate, $endDate, $eventName=null, $country, $versionName, $groupBy);
    }
  
     //////////  Supplementary helper function based on "appMetrics" API functions

    /**
     * Generates an array of all metrics for a given day
     * @return array List of all metrics (int)
     */
    public function getAllAppMetrics($startDate, $country=null, $versionName=null) {
        $objects_array = array();
            $objects_array['activeUsers'] = $this->getActiveUsers($startDate, $endDate=null, $country, $versionName);
            $objects_array['activeUsersByWeek'] = $this->getActiveUsersByWeek($startDate, $endDate=null, $country, $versionName);
            $objects_array['activeUsersByMonth'] = $this->getActiveUsersByMonth($startDate, $endDate=null, $country, $versionName);
            $objects_array['newUsers'] = $this->getNewUsers($startDate, $endDate=null, $country, $versionName);
            $objects_array['medianSessionLength'] = $this->getMedianSessionLength($startDate, $endDate=null, $country, $versionName);
            $objects_array['avgSessionLength'] = $this->getAvgSessionLength($startDate, $endDate=null, $country, $versionName);
            $objects_array['sessions'] = $this->getSessions($startDate, $endDate=null, $country, $versionName);
            $objects_array['retainedUsers'] = $this->getRetainedUsers($startDate, $endDate=null, $country, $versionName);
            $objects_array['pageViews'] = $this->getPageViews($startDate, $endDate=null, $country, $versionName);
            $objects_array['avgPageViewsPerSession'] = $this->getAvgPageViewsPerSession($startDate, $endDate=null, $country, $versionName);
         $result_array = array();
         foreach ($objects_array as $metric_name=>$object) {
                $object_array = (is_object($object)) ? $this->convertObjectToArray($object) : $object;
                $value = (isset($object_array["day"])) ? $object_array["day"]["@value"] : $object_array["@value"];
                $result_array[$metric_name] = $value;
         }
         return $result_array;       
    }
    
    //////////  "appInfo" API functions
    /// No parameters

    /**
     *  Information on a specific project
     */
    public function getApplicationInfo() {
        return $this->call('appInfo', 'getApplication', $startDate=null, $endDate=null, $eventName=null, $country=null, $versionName=null, $groupBy=null);
    }
    
    /**
     *  Information on all projects under a specific company
     */
    public function getAllApplications() {
        return $this->call('appInfo', 'getAllApplications', $startDate=null, $endDate=null, $eventName=null, $country=null, $versionName=null, $groupBy=null);
    }
    
    //////////  "eventMetrics" API functions
    // (There is no guarantee of uniqueness of users for each period. For example, a unique user counted on day 1 could be counted again on day 2 if he uses the app on both days)
    // Parameter order : getEventMetricsSummary($startDate, $endDate, $versionName)
    // Parameter order : getEventMetrics($eventName, $startDate, $endDate, $versionName)

    /**
     *  Returns a list of all events for the specified application with the following information for each
     * 
     *  Event Name              The name of the event
     *  Users Last Day          Total number of unique users for the last complete day
     *  Users Last Week         Total number of unique users for the last complete week
     *  Users Last Month    Total number of unique users for the last complete month
     *  Avg Users Last Day  The average of the number of unique users for each day in the interval
     *  Avg Users Last Week The average of the number of unique users for each week in the interval
     *  Avg Users Last Month    The average of the number of unique users for each month in the interval
     *  Total Counts            Total number of time the event occurred
     *  Total Sessions          Total number of sessions
     */
    public function getEventMetricsSummary($startDate, $endDate=null, $versionName=null) {
        return $this->call('eventMetrics', 'Summary', $startDate, $endDate, $eventName=null, $country=null, $versionName=null, $groupBy=null);
    }
    
    /**
     *  The metrics returned are:
     * 
     *  Unique Users    Total number of unique users
     *  Total Sessions  Total number of sessions
     *  Total Count Total number of time the event occurred
     *  Duration    Total   Duration of the event (Will be displayed only if the event is timed)
     *  Parameters  This will return a list of key/values. The key is the name of the parameter
     *  The values have the following metrics: name (the event name) and totalCount (the number of sessions)
     */
    public function getEventMetrics($eventName, $startDate, $endDate=null, $versionName=null) {
        return $this->call('eventMetrics', 'Event', $startDate, $endDate, $eventName, $country=null, $versionName=null, $groupBy=null);
    }
 
    //////////  Supplementary helper functions based on "eventMetrics" API functions

    /**
     * Filters the event summary and gets an array with given events only
     * @param array $events
     * @param datetime $date
     * @return array
     */
    public function getEventMetricsSummaryForEvents($events, $date) {
        $array = array();
        $date = $date->format("Y-m-d");
        $event_metrics_summary = $this->getEventMetricsSummary($date);
        $event_array = $event_metrics_summary->event;
        foreach ($event_array as $event_object) {
            $event_name = $this->convertObjectToArray($event_object)["@eventName"];
            if (in_array($event_name, $events)) {
                array_push($array, $event_object);
            }
        }
        return $this->convertObjectToArray($array);
    }

    /**
     * Returns the list of all events of the Appli
     * @return array List of all events
     */
    public function getEventList() {
        $list = array();
        $today = date("Y-m-d");
        $event_metrics_summary = $this->getEventMetricsSummary($today);
        $array = $event_metrics_summary->event;
        foreach ($array as $event_object) {
            $event_name = $this->convertObjectToArray($event_object)["@eventName"];
            array_push($list, $event_name);
        }
        return $list;
    }
    
    /**
     * Finds the array with a given parameter name in an event metric object
     */
    public function findParamInEventMetric($object, $paramName) {
        if (!is_array($object)) {
            $full_array = $this->convertObjectToArray($object);
        } else {
            $full_array = $object;
        }
        // Gets the correct part of the array
        $array = $full_array["parameters"]["key"];
        if (isset($array["value"]))
            $array = $array["value"];
        $param_array = array();
        if (null!=$array) {
            if (isset($array["@name"])) {
                $result = $this->findMatchingNameArray($array, $paramName);
            } else {
                $result = null;
                foreach ($array as $value) {
                    $returned = $this->findMatchingNameArray($value, $paramName);
                    $result = ($returned!=null) ? $returned : $result;
                }
            }
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * Finds teh matching name array
     * @param array $array
     * @param string $paramName
     * @return null|array
     */
    private function findMatchingNameArray($array, $paramName) {
        // Gets the name
        $name = isset($array["@name"]) ? $array["@name"] : $array;
        return ($name==$paramName) ? $array : null;
    }
    
    /**
     * Generates an array for parameter(s) with given events metrics
     * 
     * @param array $events Events to search the parameters for
     * @param array|string $param Parameter(s) to search the events for 
     * @return array
     */
    public function getEventsForParam($events, $param, $startDate, $endDate=null) {
        if (is_array($param)) {
            return $this->getEventsForMultipleParameters($events, $param, $startDate, $endDate);
        }
        else if (is_string($param)) {
            return $this->getEventsForSingleParameter($events, $param, $startDate, $endDate);
        }
    }

    /**
     * Generates an array of metrics for given events for a single given parameter
     * @return array
     */
    private function getEventsForSingleParameter($events, $parameter, $startDate, $endDate=null) {
        $return_array = array();
        foreach ($events as $event) {
            $event_metric_object = $this->getEventMetrics($event, $startDate, $endDate);
            $parameter_array = $this->findParamInEventMetric($event_metric_object, $parameter);
            if (isset($parameter_array["@totalCount"])) {
                $return_array[$event] = $parameter_array["@totalCount"];
            } else {
                $return_array[$event] = NULL; 
            }
        }
        return $return_array;
    }

    /**
     * Generates a multidimensionnal array of metrics for given events for each given parameter
     * @return array
     */
    private function getEventsForMultipleParameters($events, $parameters, $startDate, $endDate=null) {
        $return_array = array();
        foreach ($events as $event) {
            $event_metric_object = $this->getEventMetrics($event, $startDate, $endDate);
            foreach ($parameters as $parameter) {
                $parameter_array = $this->findParamInEventMetric($event_metric_object, $parameter);
                if (isset($parameter_array["@totalCount"])) {
                     $return_array[$parameter][$event] = $parameter_array["@totalCount"];
                } else {
                     $return_array[$parameter][$event] = null;
                }
            }
        }
        return $return_array;
    }
}
