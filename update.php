<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Police Emergency Service System</title>
<link rel="stylesheet" type="text/css" href="pess_style.css">
<?php
/* if postback via clicking Update button */
if (isset($_POST["btnUpdate"])){
  require_once 'db.php';
  // create database connection
  $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
  // Check connection
  if ($conn->connect_error) {
	  die("Connection failed: " . $conn->connect_error);
  }
   
  // update patrol car status
  $sql = "UPDATE patrolcar SET patrolcarStatusId = '".$_POST['patrolCarStatus']."' WHERE patrolcarId = '".$_POST['patrolcarId']."'";
  if ($conn->query($sql)===FALSE) {
	echo "Error: " . $sql . "<br>" . $conn->error;
  }
  
  /* if patrol car status is Arrived (4) then capture the time of arrival */
  if ($_POST["patrolcarstatus"] == '4'){
	$sql = "UPDATE dispatch SET timeArrived = NOW() WHERE timeArrived is NULL AND patrolcarId = '".$_POST['patrolcarId']."'";
	if ($conn->query($sql)===FALSE) {
	  echo "Error: " . $sql . "<br>" . $conn->error;
	}
  } 
  /* else if patrol car status is FREE (3) then capture the time of completion */  
  else if ($_POST["patrolcarstatus"] == '3'){	

	/* First, retrieve the incident ID from dispatch table handled by that patrol car */
	$sql = "SELECT incidentId FROM dispatch WHERE timeCompleted IS NULL AND patrolcarId = '".$_POST['patrolcarId']."'";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	  while ($row = $result->fetch_assoc()) {
		$incidentId = $row['incidentId'];
	  }
	}

	// next update dispatch table
	$sql = "UPDATE dispatch SET timeCompleted = NOW() WHERE timeCompleted is NULL AND patrolcarId = '".$_POST['patrolcarId']."'";
	if ($conn->query($sql)===FALSE) {
	  echo "Error: " . $sql . "<br>" . $conn->error;
	}
	   
	/* update incident table to completed (3) all patrol car attended to it are FREE now */  
	$sql = "UPDATE incident SET statusId = '3' WHERE incidentId = '$incidentId' AND NOT EXISTS (SELECT * FROM dispatch WHERE timeCompleted IS NULL AND incidentId = '$incidentId')";
	if ($conn->query($sql)===FALSE) {
	  echo "Error: " . $sql . "<br>" . $conn->error;
	}		
  }
  $conn->close();
  ?>

<script type="text/javascript">window.location="./logcall.php";</script>

<?php } ?>

</head>
<body>
<!-- Part 1 -->
<?php require_once 'nav.php'; ?>
<br><br>
<?php
if (!isset($_POST["btnSearch"])){
?>
<!--  create a form to search for patrol car based on id -->
<form name="form1" method="post"
  action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?> ">
  <table class="ContentStyle">
    <tr></tr>
    <tr>
      <td>Patrol Car ID :</td>
      <td><input type="text" name="patrolcarId" id="patrolcarId"></td>
      <td><input type="submit" name="btnSearch" id="btnSearch" value="Search"></td>
    </tr>
  </table>
</form>

<?php } 
/* if postback via clicking Search button */
else 
{
  require_once 'db.php';

  // create database connection
  $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_DATABASE);
  // Check connection
  if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
  }

  // retrieve patrol car detail
  $sql = "SELECT * FROM patrolcar WHERE patrolcarId = '".$_POST['patrolcarId']."'";
  $result = $conn->query($sql);
  
  // if the patrol car does not exist, redirect back to update.php
  if  ($result->num_rows == 0) {
	?>
		<script type="text/javascript">window.location="./update.php";</script>
	<?php } 
  // else if the patrol car found
  while ($row = $result->fetch_assoc()) {
	$patrolCarId = $row['patrolcarId'];
	$patrolCarStatusId = $row['patrolcarStatusId'];
  }
  
  // retrieve from patrolcar_status table for populating the combo box
  $sql = "SELECT * FROM patrolcarstatus";
  $result = $conn->query($sql); 
  if ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
	  $patrolCarStatusArray[$row['statusId']] = $row['statusDesc'];
	}
  }

  while ($row = $result->fetch_assoc()) {
	$patrolCarStatusArray[$row['statusId']] = $row['statusDesc'];
  }
  
  $conn->close();

?>
<!-- display a form for operator to update status of patrol car --> 
<form name="form2" method="post"
  action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?> ">
  <table>
    <tr></tr>
    <tr>
      <td>ID :</td>
      <td><?php echo $patrolCarId ?>
        <input type="hidden" name="patrolcarId" id="patrolcarId"
        value="<?php echo $patrolCarId ?>">
      </td>
    </tr>
    <tr>
      <td>Status :</td>
      <td><select name="patrolcarstatus" id="patrolcarstatus">
		<?php foreach( $patrolCarStatusArray as $key => $value){ ?>
        <option value="<?php echo $key ?>"
            <?php if ($key==$patrolCarStatusId) {?> selected="selected"
            <?php }?>
        >
            <?php echo $value ?>
        </option>
        <?php } ?>
      </select></td>
    </tr>
    <tr>
      <td><input type="reset" name="btnCancel" id="btnCancel" value="Reset"></td>
      <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="btnUpdate" id="btnUpdate" value="Update">
      </td>
    </tr>
  </table>
</form>
<?php } ?>

</body>
</html>