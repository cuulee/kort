<?php
/**
 * kort - Webservice\Fix\FixGetHandler class
 */
namespace Webservice\Fix;

use Webservice\DbProxyHandler;
use Webservice\Bug\BugHandler;

/**
 * The FixGetHandler class handles all GET requests to the fix webservice.
 */
class FixGetHandler extends DbProxyHandler
{
    /**
     * Returns the database table to be used with this Handler.
     *
     * @return the database table as a string
     */
    protected function getTable()
    {
        return 'kort.all_fixes';
    }

    /**
     * Returns the database fields to be used with this Handler.
     *
     * @return an array of database fields
     */
    protected function getFields()
    {
        return array(
            'fix_id',
            'user_id',
            'username',
            'formatted_create_date',
            'latitude',
            'longitude',
            'error_type',
            'answer',
            'falsepositive',
            'description',
            'complete',
            'valid',
            'required_votes',
            'upratings',
            'downratings',
            'osm_id',
            'osm_type',
            'schema',
            'error_id'
        );
    }

    /**
     * Returns the headers of the database fields.
     *
     * @return an array of headers
     */
    public function getHeaders()
    {
        return array(
            'fix_id'                => 'ID',
            'user_id'               => 'User ID',
            'username'              => 'Benutzername',
            'formatted_create_date' => 'Erstellungsdatum',
            'latitude'              => 'Latitude',
            'longitude'             => 'Longitude',
            'question'              => 'Auftrag',
            'error_type'            => 'Fehler',
            'answer'                => 'L&ouml;sungsvorschlag',
            'falsepositive'         => 'Nicht lösbar?',
            'description'           => 'Beschreibung des L&ouml;sungsvorschlags',
            'complete'              => 'Abgeschlossen?',
            'valid'                 => 'G&uuml;ltig?',
            'required_votes'        => 'Ben&ouml;tigte Pr&uuml;fungen',
            'upratings'             => 'Positive &Uuml;berpr&uuml;fungen',
            'downratings'           => 'Negative &Uuml;berpr&uuml;fungen',
            'votes'                 => 'Pr&uuml;fungen',
            'osm_id'                => 'OSM object ID',
            'osm_link'              => 'OSM-Objekt',
            'osm_type'              => 'OSM type',
            'edit'                  => 'Bearbeiten in OSM'
        );
    }

    /**
     * Returns all pending fixes.
     *
     * @return string|bool a JSON-encoded array of fixes if successfull, false otherwise
     */
    public function getPendingFixes()
    {
        return $this->getFixes("not complete");
    }

    /**
     * Returns all completed and valid fixes.
     *
     * @return string|bool a JSON-encoded array of fixes if successfull, false otherwise
     */
    public function getCompletedValidFixes()
    {
        return $this->getFixes("complete and valid");
    }

    /**
     * Returns all fixes for the given where clause.
     *
     * @param string $where The WHERE clause to filter the fixes.
     *
     * @return string|bool a JSON-encoded array of fixes if successfull, false otherwise
     */
    protected function getFixes($where)
    {
        $this->getDbProxy()->setWhere($where);
        $this->getDbProxy()->setOrderBy("(required_votes - upratings + downratings), create_date");
        $fixData = json_decode($this->getDbProxy()->select(), true);
        if (!$fixData) {
            return false;
        }

        $fixData = array_map(array($this, "reduceData"), $fixData);

        return json_encode($fixData);
    }

    /**
     * Reduces the returned data to be displayed.
     *
     * @param array $fix The data of a fix.
     *
     * @return array The reduced $fix
     */
    protected function reduceData(array $fix)
    {
        $fix = $this->votes($fix);
        $fix = $this->osmLink($fix);
        $fix = $this->edit($fix);

        $fix['answer'] = htmlentities($fix['answer']);
        if ($fix['falsepositive'] == "t") {
            $fix['answer'] = "Nicht lösbar";
        }

        unset($fix['osm_id']);
        unset($fix['required_votes']);
        unset($fix['upratings']);
        unset($fix['downratings']);
        unset($fix['osm_type']);
        //unset($fix['fix_id']);
        unset($fix['complete']);
        unset($fix['falsepositive']);
        unset($fix['valid']);
        unset($fix['latitude']);
        unset($fix['longitude']);
        unset($fix['user_id']);
        unset($fix['schema']);
        unset($fix['error_id']);
        return $fix;
    }

    /**
     * Returns a text represenation of the given boolean value.
     *
     * @param boolean $value The boolean value to be converted to text.
     *
     * @return string
     */
    protected function booleanToText($value)
    {
        if ($value == "t") {
            return "Ja";
        } elseif ($value == "f") {
            return "Nein";
        } else {
            return "";
        }
    }

    /**
     * Enhances the $fix with a link to OSM.
     *
     * @param array $fix The $fix to enhance.
     *
     * @return array
     */
    protected function osmLink(array $fix)
    {
        $osmUrl = "http://www.openstreetmap.org/browse/" . $fix['osm_type'] . "/" . $fix['osm_id'];
        $fix['osm_link'] = "<a href=\"" . $osmUrl . "\">" . $fix['osm_id'] . "</a>";
        return $fix;
    }

    /**
     * Enhances the $fix with the corresponding votes.
     *
     * @param array $fix The $fix to enhance.
     *
     * @return array
     */
    protected function votes(array $fix)
    {
        $fix['votes'] = "";
        if ($fix['upratings'] > 0) {
            $thumbsUp = "<img class=\"thumb\" src=\"../resources/images/validation/thumbs-up.png\" />";
            $fix['votes'] = $fix['votes'] . "+" . $fix['upratings'] . $thumbsUp;
        }
        if ($fix['downratings'] > 0) {
            $thumbsDown = "<img class=\"thumb\" src=\"../resources/images/validation/thumbs-down.png\" />";
            $fix['votes'] = $fix['votes'] . "-" . $fix['downratings'] . $thumbsDown;
        }
        return $fix;
    }

    /**
     * Enhances the $fix with an edit link for Potlatch2.
     *
     * @param array $fix The $fix to enhance.
     *
     * @return array
     */
    protected function edit(array $fix)
    {
        $potlatchUrl  = "http://www.openstreetmap.org/edit?editor=potlatch2&";
        $remoteUrl  = "http://www.openstreetmap.org/edit?editor=remote&";
        $params = "lat=" . $fix['latitude'] . "&lon=" . $fix['longitude'] . "&zoom=18";
        $keeprightUrl  = "http://www.keepright.at/report_map.php";
        $keeprightUrl .= "?schema=" . $fix['schema'] . "&error=" . $fix['error_id'];

        $fix['edit'] = "<div class=\"btn-group\">";
        $fix['edit'] = $fix['edit'] . "<a class=\"btn btn-success dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\">";
        $fix['edit'] = $fix['edit'] . "Editor";
        $fix['edit'] = $fix['edit'] . " <span class=\"caret\"></span>";
        $fix['edit'] = $fix['edit'] . "</a>";
        $fix['edit'] = $fix['edit'] . "<ul class=\"dropdown-menu\">";
        $fix['edit'] = $fix['edit'] . "<li><a target=\"_blank\" href=\"" . $potlatchUrl . $params . "\">Potlatch 2</a></li>";
        $fix['edit'] = $fix['edit'] . "<li><a target=\"_blank\" href=\"" . $remoteUrl . $params . "\">JSOM</a></li>";
        $fix['edit'] = $fix['edit'] . "<li><a target=\"_blank\" href=\"" . $keeprightUrl . "\">KeepRight</a></li>";
        $fix['edit'] = $fix['edit'] . "</ul>";
        $fix['edit'] = $fix['edit'] . "</div>";

        return $fix;
    }
}