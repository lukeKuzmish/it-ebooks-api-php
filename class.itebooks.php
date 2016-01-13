<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.curl.php';


define('ITEBOOKS_SDK_JSON', 5);
define('ITEBOOKS_SDK_ASSOC', 10);
define('ITEBOOKS_SDK_OBJECT', 15);

class ItEbooksApi {

    const SDK_VERSION       =   '0.0.1';
    const BASE_URL          =   'http://it-ebooks-api.info/v1/';
    const SEARCH_ENDPOINT   =   'search/';
    const DETAILS_ENDPOINT  =   'book/';
    const RESULTS_PER_PAGE  =   10;

    protected $c;
    protected $returnType   = ITEBOOKS_SDK_JSON;

    public function __construct() {
        $this->c = new Curl();
        $this->c->setUserAgent('itEbooksApi PHP SDK v' . self::SDK_VERSION);
        //$this->c->setDebug(true); // TODO

    } // __constructor


    /**
     *  setReturnType
     *
     *  Set the return type that the API endpoints return.  Default is to
     *  return the JSON string directly from the API, but other options
     *  are associative array and object.
     *
     *  @since  0.0.1
     *
     *  @param  int     $newReturnType  ITEBOOKS_SDK_JSON, ITEBOOKS_SDK_ASSOC,
     *                                  or ITEBOOKS_SDK_OBJECT
     *  @return mixed   int on success (newReturnType), false if incorrect param
     */ 
    public function setReturnType($newReturnType) {

        $allowedValues = array(ITEBOOKS_SDK_JSON, ITEBOOKS_SDK_ASSOC, ITEBOOKS_SDK_OBJECT);
        if (in_array($newReturnType, $allowedValues)) {
            $this->returnType = $newReturnType;
            return $newReturnType;
        }
        else {
            return false;
        }

    } // setReturnType

    /**
     *  search
     * 
     *  Use the API book search functionality.  The results are limited to 10 items per request.
     *  The function accepts a page number to request, and can also fetch any number of
     *  pages.
     *
     *  @since  0.0.1
     *  
     *  @link   http://it-ebooks-api.info/#search
     *
     *  @param  string  $query          Search term
     *  @param  int     $page           Optional. Page number to request
     *  @param  int     $pagesToRequest Optional. Number of pages to request
     *                                  Pass -1 to fetch all
     *
     *  @return array   An array of $this->returnType (JSON string, associative
     *                  array, object).  False if there is a problem with the
     *                  query or parameters.
     *
     */ 
    public function search($query, $page = 1, $pagesToRequest = 1) {

        $page = intval($page);
        $pagesToRequest = intval($pagesToRequest);

        if (($page < 1) or ($pagesToRequest == 0)) {
            // must request at least 1 page
            return false;
        }

        $isMultiRequest = ($pagesToRequest !== 1);
        echo "\nis multi request? ";
        var_dump($isMultiRequest); // TODO
        $pagesFetched = 0;
        $totalPages = $totalBooks = null;

        $toReturn = array();
        $requestCount = 0; // TODO
/*
        echo <<< TXT
pagesFetched: $pagesFetched
pagesToRequest: $pagesToRequest
totalPages: $totalPages

TXT;
*/ // TODO
        while ( ($pagesFetched !== $pagesToRequest) and ( $page !== $totalPages) ) {
            if ($requestCount++ > 100) { echo "\nrequestCount is {$requestCount} so we're exiting since that's not right\n"; break; } // TODO
            $apiData = null;
            $currUrl = self::BASE_URL . self::SEARCH_ENDPOINT . $query . '/page/' . $page;
            $jsonStr = $this->request($currUrl);
            //echo "\ncurrUrl: $currUrl\njson:\n$jsonStr\n"; // TODO
            
            $page++;
            $pagesFetched++;
            
            $apiData = json_decode($jsonStr, true);
            if ( (isset($apiData['Error'])) and ($apiData['Error'] != false) ) {
                /*
                // api error!
                if (count($toReturn) > 0) {
                    $toReturn[] = $this->convertToReturnType($jsonStr);
                }
                else {
                    $toReturn = false;
                }
                */
                throw new Exception('IT-EBooks API Error: ' . $apiData['Error']);
                return $toReturn; // ??? should this be returned (say the fourth query gave this error, why not give the user the partial data?)
            }
            if ( ($isMultiRequest) and ($totalBooks === null) ) {
                $totalBooks = $apiData['Total'];
                $totalPages = ceil($totalBooks / self::RESULTS_PER_PAGE);
            }
            
            // no reason to re-encode the JSON string into an object since it was used above
            if ($this->returnType !== ITEBOOKS_SDK_ASSOC) {
                $apiData = $this->convertToReturnType($jsonStr);
            }
            $toReturn[] = $apiData;
        }

        return $toReturn;
    } // bookSearch 
    
    /**
     *  details
     *
     *  Get details about a book by its ID.
     *
     *  @since  0.0.1
     *
     *  @link   http://it-ebooks-api.info/#book
     *
     *  @param  int     $id     Book ID (from search)
     *
     *  @return mixed   string, associative, or object based on
     *                  $this->returnType.
     *                  False if an error
     *
     *
     */
    function details($id = null) {
        
        if ($id == null) {
            // not a valid id
            return false;
        }
        
        $url = self::BASE_URL . self::DETAILS_ENDPOINT . $id;
        $jsonStr = $this->request($url);
        // TODO
        // should we check for $data['Error'] != 0 and throw an Exception?
        return $this->convertToReturnType($jsonStr);
    } // details

    // probably won't need this, since there's only 1 case where we won't have the data in the format we want
    /**
     *  convertToReturnType
     *
     *  Convert JSON string from API to user's desired return type.
     *
     *  @since  0.0.1
     *
     *  @param  string  $jsonStr    API response JSON
     *
     *  @return mixed   string, associative, or object based on
     *                  $this->returnType.
     */
    private function convertToReturnType($jsonStr) {
        switch ($this->returnType) {

            case ITEBOOKS_SDK_JSON:
                $apiData = $jsonStr;
                break;

            case ITEBOOKS_SDK_ASSOC:
                $apiData = json_decode($jsonStr, true);
                break;

            case ITEBOOKS_SDK_OBJECT:
                $apiData = json_decode($jsonStr);
                break;
        }
        return $apiData;
    }


    private function request($url) {

        return $this->c->getRequest($url);

    }

} // ItEbooksApi
