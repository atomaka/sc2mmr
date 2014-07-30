<?php
$benchmark = array(
	'total'			=> 0,
        'index'                 => 0,
        'start'                 => 0,
        'profile_load'          => 0,
        'calculations'          => 0,
);
$benchmark_start = microtime(true);
$profile = $_POST['profile'];
$w = $_POST['W'];
$k = $_POST['K'];
$c = $_POST['C'];

$constantsDefined = false;
if(($w == 'W' || $k == 'K' || $c == 'C')&&($w != 'W' || $k != 'K' || $c != 'C')) {
    //die ('if one constant is filled all constants need to be filled.');
    $error = array('Constants','Entering one of the W, K, or C constants requires you to enter all of them.');
    require 'index.php';
    exit();
} elseif($w != 'W' && $k != 'K' && $c != 'C') {
    $constantsDefined = true;
}

    
//require valid battle.net profile.
if(!preg_match('{^http://us|eu|tw|sea.battle.net}',$profile)) {
    //die('throw error, not valid profile or region cannot be checked.');
    $error = array('Battle.net Profile','You did not enter a valid Battle.net profile URL.');
    require 'index.php';
    exit();
}
    
$MAGIC_NUMBERS = array(
    'bronze'        => 500,
    'silver'        => 250,
    'gold'          => 250,
    'platinum'      => 250,
    'diamond'       => 475,    
);

$MMR_NUMBERS = array(
    'bronze'        => 0,
    'silver'        => 1000,
    'gold'          => 1250,
    'platinum'      => 1500,
    'diamond'       => 1750,
    'master'       => 2225,
);
    
    
include_once 'lib/sc2profile.php';

$benchmark['start'] = microtime(true) - $benchmark_start;
$benchmark_start = microtime(true);

$sc2 = new sc2profile($profile);

$benchmark['profile_load'] = microtime(true) - $benchmark_start;
$benchmark_start = microtime(true);

if($sc2->getProfileFound()) {
    $winPercent = $sc2->getGamesWon() / $sc2->getGamesPlayed();
    $rankPointDifferential = 0;
    if($constantsDefined) {
        $rankPointDifferential = $sc2->getCharacterRating() + ($sc2->getBonusPool() * $w * 2) - findMaxBonusPool();
        $matchMakingRating =  $rankPointDifferential + $k + $c;
    } else {
        $rankPointDifferential =  $sc2->getCharacterRating() + ($sc2->getBonusPool() * $winPercent * 2) - findMaxBonusPool();
        $matchMakingRating = $rankPointDifferential + $MMR_NUMBERS[$sc2->getCharacterLeague()];
    }
    
    //only if we have a game history (require 10 minumum)
    if(count($sc2->getMatchHistory()) >= 10) {
        $averageWin = calculateAverageWin($sc2->getMatchHistory());
        $averageLoss = calculateAverageLoss($sc2->getMatchHistory());
        $gamesToPromotion = 5 + ($MAGIC_NUMBERS[$sc2->getCharacterLeague()] - $rankPointDifferential) / ($winPercent * $averageWin + ($winPercent - 1) * $averageLoss);
    }

        $obsGamesToPromotion = 5 + ($MAGIC_NUMBERS[$sc2->getCharacterLeague()] - $rankPointDifferential) / (24 * $winPercent - 11);
} else {
    //die('profile not found or could not be read.');
    $error = array('Profile','The profile you entered could not be read.  Please recheck your Battle.net profile URL or try again later.');
    require 'index.php';
    exit();
}
$benchmark['calculations'] = microtime(true) - $benchmark_start;
$benchmark_start = microtime(true);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"> 
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
		<title>Starcraft II Promotion Prediction</title>
		<link rel="stylesheet" type="text/css" href="css/main.css" />
		<link rel="stylesheet" type="text/css" href="css/niftyCorners.css" />
                <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js" type="text/javascript"></script>
                <script type="text/javascript" src="js/niftycube.js"></script>
		<script type="text/javascript" src="js/niftylayout.js"></script>
                <link rel="shortcut icon" href="favicon.ico" />
                <script type="text/javascript">	
$(document).ready(function() {
    $('.default').each(function() {
        var default_value = this.value;
        $(this).focus(function() {
            if(this.value == default_value) {
                this.value = '';
            }
        });
        $(this).blur(function() {
            if(this.value == '') {
                this.value = default_value;
            }
        });
    });
    //contact form
    $('#contactsubmit').click(function(e){
        e.preventDefault();

        var error = false;
        var email = $('#contactemail').val();
        var message = $('#contactcomment').val();

        if(email.length == 0 || email.indexOf('@') == '-1'){
            var error = true;
            $('#email_error').fadeIn(500);
        }else{
            $('#email_error').fadeOut(500);
        }
        if(message.length == 0 || message == 'Enter your comment here.'){
            var error = true;
            $('#message_error').fadeIn(500);
        }else{
            $('#message_error').fadeOut(500);
        }

        if(error == false){
            $('#contactsubmit').attr({'disabled' : 'true', 'value' : 'Sending...' });
            $.post("contact.php", {"contactemail":$('#contactemail').val(),"contactcomment":$('#contactcomment').val()},function(result){
                if(result == 'sent'){
                    $('#formRemove').remove();
                    $('#mail_success').fadeIn(500);
                }else{
                    $('#mail_fail').fadeIn(500);
                    $('#contactsubmit').removeAttr('disabled').attr('value', 'Send The Message');
            }
            });
        }
    });
});
                </script>
	</head>	
	<body>
                <br/><br/>
		<div id="content">
			<div id="column1a">
				<h2>about</h2>
				<div id="information" class="box">
                                    <p>These results currently assume that you are playing in an average league.  If your league strength differs,
                                    your results will be inaccurate.</p>
                                    <p>Currently, two predictions are displayed.  The first prediction is based on an average of observed wins and
                                    loss point values and assumes you will win 13 points and lose 11 points.  The second is made using your played
                                    games history.  This can only be made if your Battle.net profile recently played history has enough games.</p>
				</div>
                                <h2>contact</h2>
				<div id="contactme" class="box center">
                                    <br/>
                                    <form id="contactForm">
                                        <span id="formRemove">
                                            <div id="email_error" class="error">Please enter a valid email.</div>  
                                            <input id="contactemail" type="text" name="email" value="Enter your email here." class="contacttext default"/><br/><br/>
                                            <div id="message_error" class="error">Please enter a comment.</div>  
                                            <textarea id="contactcomment" name="comment" class="contactcomment default">Enter your comment here.</textarea><br/>

                                            <div id="mail_fail" class="error">Please try again later.</div>
                                            <p id="contactsubmit_p"><input id="contactsubmit" type="submit" value="Contact Me" class="contactbutton" /></p>
                                        </span>
                                        <div id="mail_success" class="success">Thank your for your feedback.</div> 
                                    </form>
				</div>
			</div>
			<div id="column1b">
				<h2>your promotion</h2>
				<div id="prediction" class="box">
                                    <table class="mmr">
                                        <tr>
                                            <td class="mmrHeading">Rank Point Differential</td>
                                            <td class="mmrHeading">Match Making Rating</td>
                                        </tr>
                                        <tr>
                                            <td class="mmrData"><?php printf('%.2f',$rankPointDifferential); ?></td>
                                            <td class="mmrData"><?php printf('%.2f',$matchMakingRating); ?></td>
                                        </tr>
                                    </table><br/><br/>
                                    Estimated games to promotion:<br/>
<?php
if($sc2->getCharacterLeague() == 'master') {
?>
                                    You can not be promoted any further.
<?php
} else {
?>
                                    Based on observed win/loss averages:  <?php echo ceil($obsGamesToPromotion) ?> games to be promoted.<br/>
                                    Based on your recently played trends: 
<?php
    if($gamesToPromotion != 0) {
?>
                                    <?php echo ceil($gamesToPromotion) ?> games to be promoted.<br/>
<?php
    } else {
?>
                                    Not enough data to predict.
<?php
    }
}
?>
                                    <br/><br/>Your MMR was calculated using <b>MMR = P + U*W*2 - B + K + C</b> with the following values:
                                    <table class="formula">
                                        <tr>
                                            <td class="formulaData">P</td>
                                            <td class="formulaData">Rank Points</td>
                                            <td class="formulaData"><?php echo $sc2->getCharacterRating(); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="formulaData">U</td>
                                            <td class="formulaData">Unspent Bonus Pool</td>
                                            <td class="formulaData"><?php echo $sc2->getBonusPool(); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="formulaData">W</td>
                                            <td class="formulaData">Win Percentage</td>
                                            <td class="formulaData"><?php echo ($w == 'W') ? $winPercent : $w ?></td>
                                        </tr>
                                        <tr>
                                            <td class="formulaData">B</td>
                                            <td class="formulaData">Total Bonus Pool</td>
                                            <td class="formulaData"><?php echo findMaxBonusPool(); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="formulaData">K</td>
                                            <td class="formulaData">Divison Modifier</td>
                                            <td class="formulaData"><?php echo ($k == 'K') ? 0 : $k ?></td>
                                        </tr>
                                        <tr>
                                            <td class="formulaData">C</td>
                                            <td class="formulaData">League Conversion Constant</td>
                                            <td class="formulaData"><?php echo ($c == 'C') ? $MMR_NUMBERS[$sc2->getCharacterLeague()] : $c ?></td>
                                        </tr>
                                    </table>

				</div>
                                <h2>predict again</h2>
				<div id="form" class="box center">
                                    <form action="predict.php" method="POST">
                                    <br/>
                                    <input type="text" id="profile" name="profile" value="Enter Your Battle.net Profile URL Here" class="text default"/> <br/><br/>
                                    Enter the following values only if you know what you are doing:<br/><br/>
                                    <input type="text" id="W" value="W" name="W" class="optionaltext default"/>
                                    <input type="text" id="K" value="K" name="K" class="optionaltext default"/>
                                    <input type="text" id="C" value="C" name="C" class="optionaltext default"/><br/><br/>
                                    <input type="submit" disabled="disabled" class="button" value="Make the Prediction!" />
                                    </form><br/>
				</div>
			</div>
			<div class="clear">&nbsp;</div>
		</div>
		<br/>
<?php
$benchmark['index'] = microtime(true) - $benchmark_start;

$max = array('part' => 'none','time' => 0);
foreach($benchmark as $part=>$time) 
{
    if($time > $max['time'])
    {
       $max['time'] = $time;
       $max['part'] = $part;
    }
    $benchmark['total'] += $time;
}
?>
                <div id="benchmark">Prepared in <?php printf('%.1f',$benchmark['total']); ?>s.</div>
		<div id="copyright">&copy; Andrew Tomaka 2011.</div>
		<br/><br/>
	</body>
</html>

<!--Array
<?php
print_r($benchmark);
?>
--><?php
//find the max bonus pool based on time assuming 2677 points at 1297620086
function findMaxBonusPool() {
    $minutesPassed = (time() - 1297620086) / 60;
    return 2677 + ($minutesPassed / 112);
}

function calculateAverageWin($matches) {
    $wins = 0;
    $winsTotal = 0;
    foreach($matches as $match) {
        if($match > 0) {
            $wins++;
            $winsTotal += ($match / 2);
        }
    }
    
    return $winsTotal / $wins;
}

function calculateAverageLoss($matches) {
    $losses = 0;
    $lossesTotal = 0;
    foreach($matches as $match) {
        if($match < 0) {
            $losses++;
            $lossesTotal += $match;
        }
    }
    
    return abs($lossesTotal) / $losses;
}
?>