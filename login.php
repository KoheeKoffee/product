<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "product_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


$user = $conn->real_escape_string($_POST['username']);
$pass = $_POST['password'];

$sql = "SELECT * FROM users WHERE username='$user' OR email='$user'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();

  if (password_verify($pass, $row['password'])) {
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['first_name'] = $row['first_name'];
    $_SESSION['last_name'] = $row['last_name'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['email'] = $row['email'];

    header("Location: index.php");
    exit();
  } else {
    echo "<h3> Invalid password. <a href='login.html'>Try again</a></h3>";
  }
} else {
  echo "<h3> No user found. <a href='login.html'>Try again</a></h3>";
}

$conn->close();
?>
