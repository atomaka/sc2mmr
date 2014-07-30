<?php
 
class sc2profile {
    private $profileFound = true;
    
    private $bnetProfileLink;
    private $divisonLink;
    
    private $divisionStandings = array();
    private $matchHistory = array();
    
    private $characterName;
    private $characterRating;
    private $characterLeague;
    private $gamesPlayed;
    private $gamesWon;
    private $bonusPool;
    
    public function __construct($profile) {
        if(preg_match('{/^}',$profile)) $this->bnetProfileLink = $profile;
        else $this->bnetProfileLink = $profile . '/';
        $this->parseProfile();
        if($this->profileFound) {
            $this->parseDivision();
            $this->parseMatchHistory();
        }
        
    }
    
    public function getDivisionLink() {
        return $this->divisionLink;
    }
    
    public function getCharacterName() {
        return $this->characterName;
    }
    
    public function getCharacterLeague() {
        return $this->characterLeague;
    }
    
    public function getGamesPlayed() {
        return $this->gamesPlayed;
    }
    
    public function getGamesWon() {
        return $this->gamesWon;
    }
    
    public function getBonusPool() {
        return $this->bonusPool;
    }
    
    public function getDivisionStandings() {
        return $this->divisionStandings;
    }
    public function getMatchHistory($count = 0) {
        if($count == 0) $count = count($this->matchHistory);
        return array_slice($this->matchHistory,0,$count);
    }
    
    public function getMaxBonusPool() {
        return $this->maxBonusPool;
    }
    
    public function getCharacterRating() {
        return $this->characterRating;
    }
    
    public function getProfileFound() {
        return $this->profileFound;
    }
    
    private function parseProfile() {
        $profileHtml = @file($this->bnetProfileLink);
        if(!$profileHtml) {
            $this->profileFound = false;
            return;
        }
        
        $onesFound = false;
        $divisionFound = false;
        $characterFound = false;
        foreach($profileHtml as $profileLine) {
            if(!$characterFound) {
                if(preg_match('{<a href="/sc2/.*/profile/.*/.*/(.*)/" rel="np">}',$profileLine,$characterMatch)) $characterFound = true;
            }
            if(preg_match('/#best-team-1/',$profileLine)) $onesFound = true;
            if(!$onesFound) continue; 

            if(!$divisionFound) {
                if(preg_match('{a href="(.*)#current-rank"><img src="/sc2/static/images/icons/league/(.*)-medium.png" alt=}',$profileLine,$divisionMatch)) $divisionFound = true;
            }
            if(!preg_match('{<strong>Record:</strong> (.*) - (.*)}',$profileLine,$recordMatch)) continue;
            
            break;
        }
        
        preg_match('{(http://.*.battle.net)}',$this->bnetProfileLink,$regionMatch);
        $this->divisionLink = $regionMatch[1] . $divisionMatch[1];
        $this->gamesPlayed = $recordMatch[1] + $recordMatch[2];
        $this->gamesWon = $recordMatch[1];
        $this->characterName = $characterMatch[1];
        $this->characterLeague = $divisionMatch[2];
    }
    
    private function parseDivision() {
        $divisionStandings = array();
        $divisionHtml = file($this->divisionLink);
        
        $standingsHtml = array();
        $standingsCount = 0;
        $standingsFound = false;
        $bonusFound = false;
        foreach($divisionHtml as $divisionLine) {
            if(!$bonusFound) {
                if(preg_match('{Bonus Pool: <span>(.*)</span></span>}',$divisionLine,$bonusMatch)) $bonusFound = true;
            }
            if(preg_match('/<table class="data-table">/',$divisionLine)) $standingsFound = true;
            if(!$standingsFound) continue;
            if(preg_match('{</table>}',$divisionLine)) break;
            
            if(preg_match('{<tr>}',$divisionLine)) $standingsCount++;
            $standingsHtml[$standingsCount] .= $divisionLine;
        }
        
        $rankTemplate = <<<RANKTEMPLATE
{<tr.*><tdclass="align-center"style="width:15px"data-tooltip="JoinedDivision:(.*)">.*</td><tdclass="align-center"style="width:40px">.*</td><td><ahref="/sc2/en/profile/.*/"class="race-.*"data-tooltip="#player-info-.*">(.*)</a><divid="player-info-.*"style="display:none"><divclass="tooltip-title">.*</div><strong>HighestRank:</strong>.*<br/><strong>PreviousRank:</strong>.*<br/><strong>FavoriteRace:</strong>.*</div></td><tdclass="align-center">(.*)</td><tdclass="align-center">(.*)</td><tdclass="align-center">(.*)</td></tr>}
RANKTEMPLATE;
        
        foreach($standingsHtml as $standingsLine) {
            $standingsLine = preg_replace('/[\s]+/m','',$standingsLine);
            
            preg_match($rankTemplate,$standingsLine,$standingMatch);
            
            array_shift($standingMatch);
            $divisionStandings[] = $standingMatch;

            if($standingMatch[1] == $this->characterName) $this->characterRating = $standingMatch[2];
        }

        $this->divisionStandings = $divisionStandings;
        $this->bonusPool = $bonusMatch[1];
    }
    
    private function parseMatchHistory() {
        $matches = array();
        $matchHistoryHtml = file($this->bnetProfileLink . 'matches');
        
        $matchHtml = array();
        $matchesFound = false;
        $matchCount = 0;
        foreach($matchHistoryHtml as $matchHistoryLine) {
            if(preg_match('/<table class="data-table">/',$matchHistoryLine)) $matchesFound = true;
            if(!$matchesFound) continue;
            if(preg_match('{</table>}',$matchHistoryLine)) break;
            
            if(preg_match('{<tr class="(.*)">}',$matchHistoryLine)) $matchCount++;
            $matchHtml[$matchCount] .= $matchHistoryLine;
        }
        
        array_shift($matchHtml);
        array_shift($matchHtml);  
        

        $matchTemplate = <<<MATCHTEMPLATE
{<trclass="match-rowsolo"><tdstyle="width:15px"data-tooltip="#match-mode-.*"><imgsrc="/sc2/static/images/icons/ladder/view-more.gif"alt="\+"/><divid="match-mode-.*"style="display:none"><strong>Type:</strong>1v1<br/><strong>Speed:</strong>Faster</div></td><td>.*</td><tdclass="align-center">1v1</td><td><spanclass=".*">.*</span>\(<spanclass=".*">(.*)</span>\)</td><tdclass="align-right">.*</td></tr>}
MATCHTEMPLATE;
        
        foreach($matchHtml as $matchLine) {
            $matchLine = preg_replace('/[\s]+/m','',$matchLine);

            preg_match($matchTemplate,$matchLine,$matchMatch);
            
            if($matchMatch[1] != '') $matches[] = $matchMatch[1];
        }
        
        $this->matchHistory = $matches;
    }
}

?>
