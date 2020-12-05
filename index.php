<?php
ini_set('memory_limit','3G');
include 'SpellCorrector.php';


// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$pageRank = (isset($_REQUEST['sort'])&&$_REQUEST['sort']=='pagerank') ? "pageRankFile desc": null;
$results = false;

$correct = "";
$correct1= "";
$output = "";
$div = false;
if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/mycore');
  if ( ! $solr->ping() ) {
    echo 'Solr service not responding.';
    exit;
  }
  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
    $additionalParameters=$pageRank ? array('sort'=>"pageRankFile desc") : null;

    $word = explode(" ",$query);
    $spell= $word[sizeof($word)-1];
    for($i=0;$i<sizeof($word);$i++){
        ini_set('memory_limit',-1);
        ini_set('max_execution_time',300);
	$che = SpellCorrector::correct($word[$i]);

	if($correct != "")
	$correct = $correct."+".trim($che);
	else
	$correct = trim($che);
	$correct1 = $correct1."+".trim($che);
    }
	$correct1 = str_replace("+"," ",$correct);
        $div = false;
	
	if(strtolower($query) == strtolower($correct1)){
           $results = $solr->search($query, 0, $limit,$additionalParameters);
        }else{
	   $div = true;
	   $results = $solr->search($query, 0, $limit,$additionalParameters);
	   $link = "http://localhost:8000/index.php?q=$correct&sort=$pageRank";
	   $output = "Did you mean: <a href='$link'><i>$correct1</i></a>?"; 
	}
    
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}
?>
<?php

    /* Map Rows and Loop Through Them */
    $rows   = array_map('str_getcsv', file('URLtoHTML_fox_news.csv'));
    $header = array_shift($rows);
    $csv    = array();
    foreach($rows as $row) {
        $csv[] = array_combine($header, $row);
    }

function findURL($name){
	$name = end(explode("/",$name));
	
	global $csv;
	foreach($csv as $record){
		if($record["filename"] == $name)return $record["URL"];
	}
}

?>



<html>
  <head>
    <title>PHP Solr Client Example</title>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  </head>
  <body>
<h1>Solr Search</h1>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" list="searchresults" autocomplete="off"/>
<datalist id="searchresults"></datalist>
      <input type="submit"/>
      <label for="q">PageRank:</label>
      <input id="sort_page" name="sort" type="radio" value="pagerank"/>
      <label for="q">Lucene:</label>
      <input id="sort_lucene" name="sort" type="radio" value="lucene"/>
    </form>
<script>
$(function() {
var URL_PREFIX = "http://localhost:8983/solr/mycore/suggest?q=";
var URL_SUFFIX = "&wt=json&indent=true";
var count = 0;
var tags = [];
$("#q").autocomplete({
	source : function(request, response)
	{
		var correct = "", before = "";
		var query = $("#q").val().toLowerCase();
		var character_count = query.length - (query.match(/ /g) || []).length;
		var space = query.lastIndexOf(' ');
		if(query.length-1>space && space !=-1){
		correct = query.substr(space+1);
		before = query.substr(0,space);}
		else{
		correct = query.substr(0);
		}
		var URL = URL_PREFIX+ correct + URL_SUFFIX;
		$.ajax({
		url : URL,
		success : function(data){
		var js = data.suggest.suggest;
var docs = JSON.stringify(js);
          var jsonData = JSON.parse(docs);
          var result =jsonData[correct].suggestions;
          var j=0;
          var stem =[];
          for(var i=0;i<5 && j<result.length;i++,j++){
            if(result[j].term==correct)
            {
              i--;
              continue;
            }
            for(var k=0;k<i && i>0;k++){
              if(tags[k].indexOf(result[j].term) >=0){
                i--;
                continue;
              }
            }
            if(result[j].term.indexOf('.')>=0 || result[j].term.indexOf('_')>=0)
            {
              i--;
              continue;
            }
            var s =(result[j].term);
            if(stem.length == 5)
              break;
            if(stem.indexOf(s) == -1)
            {
              stem.push(s);
              if(before==""){
                tags[i]=s;
              }
              else
              {
                tags[i] = before+" ";
                tags[i]+=s;
              }
            }
          }
          console.log(tags);
          response(tags);
        },
        dataType : 'jsonp',
        jsonp : 'json.wrf'
      });
      },
      minLength : 1
    })
   });
</script>
<?php
if($div)
echo "<div style='color:#ff0000;font-size:13px;'> <b>".$output."</b></div>";
// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      
        <table style="text-align: left">
<?php
    // iterate document fields / values
    foreach ($doc as $field => $value)
    {
	if($field == 'title' )$title = $value;
	if($field == 'id' )$id = $value;
	if($field == 'description' )$description = $value;
    }
?>
	<tr>
		<th><?php echo htmlspecialchars('title', ENT_NOQUOTES, 'utf-8'); ?></th>
	<td>
		<a href=<?php echo  findURL($id)?>>
		<?php echo htmlspecialchars($title, ENT_NOQUOTES, 'utf-8'); ?>
		</a>
	</td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars('url', ENT_NOQUOTES, 'utf-8'); ?></th>
		
		<td>
			<a href=<?php echo  findURL($id)?>>
			<?php echo htmlspecialchars(findURL($id), ENT_NOQUOTES, 'utf-8'); ?>
			</a>
		</td>
		
	</tr>
	<tr>
		<th><?php echo htmlspecialchars('id', ENT_NOQUOTES, 'utf-8'); ?></th>
		<td><?php echo htmlspecialchars($id, ENT_NOQUOTES, 'utf-8'); ?></td>
	</tr>
	<tr>
		<th><?php echo htmlspecialchars('description', ENT_NOQUOTES, 'utf-8'); ?></th>
		<td><?php echo htmlspecialchars($description ? $description : "NA", ENT_NOQUOTES, 'utf-8'); ?></td>
	</tr>

        </table>
        <br>
<?php
  }
?>
    
<?php
}
?>
  </body>
</html>
