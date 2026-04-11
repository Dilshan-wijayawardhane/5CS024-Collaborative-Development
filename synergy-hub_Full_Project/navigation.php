<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Campus Navigation</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
font-family:Arial;
background:#0f172a;
color:white;
text-align:center;
padding:40px;
}

.container{
background:rgba(255,255,255,0.1);
padding:40px;
border-radius:15px;
width:400px;
margin:auto;
}

select{
width:100%;
padding:12px;
border-radius:8px;
border:none;
margin-bottom:20px;
font-size:16px;
}

button{
padding:12px 20px;
border:none;
border-radius:8px;
background:#22d3ee;
color:black;
font-weight:bold;
cursor:pointer;
}

button:hover{
background:#0ea5e9;
}

</style>
</head>

<body>

<h2>📍 Campus Navigation</h2>

<div class="container">

<select id="building">

<option value="">Select Location</option>

<option value="CINEC Campus Malabe">Main Campus</option>

<option value="CINEC Maritime Campus Malabe">Maritime Faculty</option>

<option value="CINEC Library Malabe">CINEC Library</option>

<option value="CINEC Auditorium Malabe">Main Auditorium</option>

<option value="CINEC Cafeteria Malabe">Campus Cafeteria</option>

<option value="Malabe Bus Stand">Malabe Bus Stand</option>

</select>

<button onclick="navigate()">Get Directions</button>

</div>

<script>

// Opens Google Maps with the selected location as the destination
function navigate(){

let place = document.getElementById("building").value;

if(place==""){
alert("Please select a building");
return;
}

// Build Google Maps search URL
let url = "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(place);

// Open in new tab
window.open(url,"_blank");

}

</script>

</body>
</html>