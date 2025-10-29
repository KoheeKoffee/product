<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "product_db"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$first_name = $conn->real_escape_string($_POST['first_name']);
$last_name = $conn->real_escape_string($_POST['last_name']);
$user = $conn->real_escape_string($_POST['username']);
$email = $conn->real_escape_string($_POST['email']);
$pass = password_hash($_POST['password'], PASSWORD_DEFAULT); 

$sql = "INSERT INTO users (first_name, last_name, username, email, password) 
        VALUES ('$first_name', '$last_name', '$user', '$email', '$pass')";

if ($conn->query($sql) === TRUE) {
  echo "<h2>Registration successful!</h2>";
  echo "<a href='login.html'>Go to Login</a>";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
