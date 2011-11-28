<?php // JobTracker.php

// Job Application Status Tracker Program
// Kalev Roomann-Kurrik
// Last Updated: 11/28/2011

// Connect to the correct MySQL database
$db_server = mysql_connect("localhost","root",NULL);
if(!$db_server) die("Unable to connect to MySQL: " . mysql_error());
mysql_select_db("jobs") or die("Unable to select database: " . mysql_error());

// Get the $_POST information from the form and add an entry to the database
if(isset($_POST['month']) &&
   isset($_POST['day']) &&
   isset($_POST['year']) &&
   isset($_POST['company']) &&
   isset($_POST['location']) &&
   isset($_POST['job']) &&
   isset($_POST['status']))
{
	// Store the $_POST values in variables
	$month = get_post('month');
	$day = get_post('day');
	$year = get_post('year');
	$company = get_post('company');
	$location = get_post('location');
	$job = get_post('job');
	$status = get_post('status');
	$id = "NULL";
	
	// Add the entry to the myjobs table in the jobs database
	
	// Use placeholders to prevent SQL injections
	$query = 'PREPARE statement FROM "INSERT INTO myjobs VALUES(?,?,?,?,?,?,?,?)"';
	if(!mysql_query($query))
	{
		echo "Error in sending PREPARE statement: $query<br />" .
		mysql_error() . "<br /><br />";
	}
	
	$query = 'SET @month = "' . $month . '",' .
			 '@day = "' . $day . '",' .
			 '@year = "' . $year .'",' .
			 '@company = "' . $company .'",' .
			 '@location = "' . $location . '",' .
			 '@job = "' . $job . '",' .
			 '@status = "' . $status . '",' .
			 '@id = "' . $id . '"';

	if(!mysql_query($query))
	{
		echo "Error in sending SET statement: $query<br />" .
		mysql_error() . "<br /><br />";
	}
	
	$query = 'EXECUTE statement USING @month,@day,@year,@company,@location,@job,@status,@id';
	if(!mysql_query($query))
	{
		echo "Error in sending EXECUTE statement: $query<br />" .
		mysql_error() . "<br /><br />";
	}
	
	$query = 'DEALLOCATE PREPARE statement';
	mysql_query($query);
}

// Update the status of a current entry
if (isset($_POST['commit']))
{
	
	// retrieve the id of the job application being modified 
	// and its associated new status
	$id = get_post('id');
	
	$update = get_post("newStatus$id");
	
	// As long as there is a non-empty value selected
	// for the new status update the status of the job application
	if($update != "")
	{
		$query = "UPDATE myjobs SET status='$update' WHERE id='$id'";
	
	
		if(!mysql_query($query))
		{
			echo "Error in changing status of job application: $query<br />" .
			mysql_error(). "<br /><br />";
		}
	}
}

// Get today's date
// For providing a default value for the Date input field
// and for listing the saved job applications from today's date backwards
$today = getdate();
$month = $today['mon'];
$day = $today['mday'];
$year = $today['year'];

// Display the heading and form
echo <<<_END
<html>
<head>
	<title>Job Tracker</title>
	<link type="text/css" rel="stylesheet" href="JobTracker.css" />
</head>
<body>
	<div id="Title">
	<h1>JobPage</h1>
	</div>
	
	<div id="Submission">
	<form action="jobTracker.php" method="post"><pre>
	    Date <input type="text" name="month" size="2" maxlength="2" value=$month /><input type="text" name="day" size="2" maxlength="2" value=$day /><input type="text" name="year" size="4" maxlength="4" value=$year />
	 Company <input type="text" name="company" />
	Location <input type="text" name="location" />
	     Job <input type="text" name="job" />
	  Status <select name="status" size="1">
	<option value="Applied">Applied</option>
	<option value="Phone Interview">Phone Interview</option>
	<option value="On-site Interview">On-site Interview</option>
	<option value="Rejected">Rejected</option>
	<option value="Accepted">Accepted</option>
	</select><br />
	           <input type="submit" value="Add Job" />
	</pre>
	</form>	
	</div>
	
	<div id="View_Selection">
		<form action="jobTracker.php" method="post">
		View By: 
		<label><input type="radio" name="view" value="1" />Date</label>
		<label><input type="radio" name="view" value="2" />Company</label>
        <label><input type="radio" name="view" value="3" />Location</label>
		<label><input type="radio" name="view" value="4" />Status</label>
		<input type="submit" value="Change View" />
		</form>
	</div>
	
	<div id="Results">
	
_END;

// Determine which organization of database entries to use
// based on the view selected


if (!isset($_POST['view']) || (get_post('view') == 1))
{
		
// Display the current contents of the database
// by date with the most recent entries first

// Start looking for any entries at the current day
// Keep on moving backwards until you reach 10/30/2011
$dateOK = true;

while($dateOK)
{	
	// Query the database for all entries for this particular date
	$query = "SELECT * FROM myjobs WHERE month=$month AND day=$day AND year=$year";
	$result = mysql_query($query);
	if(!$result)
	{
		echo "Date lookup failed: $query<br />" . mysql_error() . "<br />";
	}
	
	// Get number of rows returned
	$rows = mysql_num_rows($result);


	// Display Date Heading if number of rows > 0
	if($rows > 0)
	{
				
echo <<<_END
		
	<h2>$month-$day-$year</h2>
	<table>
	<tr>
	<th>Company</th>				
	<th>Location</th>				
	<th>Job</th>				
	<th>Status</th>
	<th>Update</th>
	<th></th>
	</tr>
_END;
		
		
		
		// Display contents of query
		for($j = 0; $j < $rows; ++$j)
		{
			$row = mysql_fetch_row($result);
			
echo <<<_END
		
	<tr>
	<td>$row[3]</td>	
	<td id="location">$row[4]</td>
	<td>$row[5]</td>
	<td>$row[6]</td>
	<td id="updateColumns">
		<form action="jobTracker.php" method="post">
		<select name="newStatus$row[7]" size="1" onchange="store(this.value)">
			<option value=""></option>
			<option value="Applied">Applied</option>
			<option value="Phone Interview">Phone Interview</option>
			<option value="On-site Interview">On-site Interview</option>
			<option value="Rejected">Rejected</option>
			<option value="Accepted">Accepted</option>
		</select>
		<input type="hidden" name="commit" value="yes" />
		<input type="hidden" name="id" value="$row[7]" />
		<input type="submit" value="Update Status" />
		</form>
	</td>
	</tr>
			
_END;
		}
		
		echo "</table>\n";
	}
	
	
	if($rows > 0) echo "<br />\n";
	
	
	// Go to the next previous date in the calendar
	if($day > 1) --$day;
	elseif(($day == 1) && ($month > 1))
	{
		--$month;
		switch ($month)
		{
			case "11": $day = 30;
				break;
			case "10": $day = 31;
				break;
			case "9": $day = 30;
				break;
			case "8": $day = 31;
				break;
			case "7": $day = 31;
				break;
			case "6": $day = 30;
				break;
			case "5": $day = 31;
				break;
			case "4": $day = 30;
				break;
			case "3": $day = 31;
				break;
			case "2": $day = 28;
				break;
			case "1": $day = 31;
				break;
		}
	}
	else // need to switch over to previous year
	{
		--$year;
		$month = 12;
		$day = 31;
	}
	
	// Only go back through dates until 6/1/2011
	if(($month == 6) && ($day == 1) && ($year == 2011)) $dateOK = false;
}

echo "</div>\n";

}
else
{
	$view = get_post('view');
	
	// Select the correct query statement based on the view chosen
	switch($view)
	{
		case 2:
		$query = "SELECT * FROM myjobs ORDER BY company";
		break;
		case 3:
		$query = "SELECT * FROM myjobs ORDER BY location";
		break;
		case 4:
		$query = "SELECT * FROM myjobs ORDER BY status";
		break;
		default:
		break;
	}
	
	
	// Retrieve and display the DB contents for the view selected
	$result = mysql_query($query);
	if(!$result)
	{
		echo "Date lookup failed: $query<br />" . mysql_error() . "<br />";
	}
	
	// Get number of rows returned
	$rows = mysql_num_rows($result);


	// Display Heading
				
echo <<<_END
		
	<table>
	<tr>
	<th>Company</th>				
	<th>Location</th>				
	<th>Job</th>				
	<th>Status</th>
	<th>Update</th>
	<th></th>
	</tr>
_END;
		
		
	// Display contents of query
	for($j = 0; $j < $rows; ++$j)
	{
		$row = mysql_fetch_row($result);
			
echo <<<_END
		
	<tr>
	<td>$row[3]</td>	
	<td id="location">$row[4]</td>
	<td>$row[5]</td>
	<td>$row[6]</td>
	<td id="updateColumns">
		<form action="jobTracker.php" method="post">
		<select name="newStatus$row[7]" size="1" onchange="store(this.value)">
			<option value=""></option>
			<option value="Applied">Applied</option>
			<option value="Phone Interview">Phone Interview</option>
			<option value="On-site Interview">On-site Interview</option>
			<option value="Rejected">Rejected</option>
			<option value="Accepted">Accepted</option>
		</select>
		<input type="hidden" name="commit" value="yes" />
		<input type="hidden" name="id" value="$row[7]" />
		<input type="submit" value="Update Status" />
		</form>
	</td>
	</tr>
			
_END;
	}
		
		echo "</table>\n";
}
	
	
// Display the footer information
echo <<<_END
<div id="Footer">
<pre>
Created by: Kalev Roomann-Kurrik
     Last Updated: 11/28/2011
</pre>
</div>

</body>
</html>
_END;


// Retrieves a variable from $_POST while sanitizing
// the string to help prevent MySQL injection and HTML injection
function get_post($var)
{
	if(get_magic_quotes_gpc()) $var = stripslashes($string);
	return htmlentities(mysql_real_escape_string($_POST[$var]));
}

?>