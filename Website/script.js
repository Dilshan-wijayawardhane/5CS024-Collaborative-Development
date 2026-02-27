function toggleSidebar(){
  const s=document.getElementById("sidebar");
  s.style.left = s.style.left==="0px" ? "-220px":"0px";
}

function toggleTheme(){
  document.body.classList.toggle("dark");
}
document.querySelectorAll("a").forEach(link=>{
link.addEventListener("click",e=>{
const href=link.getAttribute("href");
if(href && href.includes(".html")){
e.preventDefault();
document.body.style.opacity="0";
setTimeout(()=>{window.location=href},300);
}
});
});

function toggleTheme(){
document.body.classList.toggle("dark");

/* save user preference */
localStorage.setItem(
"theme",
document.body.classList.contains("dark")?"dark":"light"
);
}

/* load saved theme */
if(localStorage.getItem("theme")==="dark"){
document.body.classList.add("dark");
}

function addPoints(amount){
const value=document.getElementById("pointsValue");
const box=document.getElementById("pointsBox");

let current=parseInt(value.textContent);
current+=amount;

value.textContent=current;

/* trigger animation */
box.classList.add("active");
setTimeout(()=>box.classList.remove("active"),500);
}

function simulateScan(){

/* show result */
document.getElementById("qrResult").style.display="block";

/* reward points */
if(typeof addPoints==="function"){
addPoints(10);
}
}


function toggleProfileMenu(e){
  e.stopPropagation();
  document.getElementById("profileMenu").classList.toggle("show");
}

function logout(){
  localStorage.removeItem("loggedIn");
  window.location="login.html";
}

document.addEventListener("click", ()=>{
  const menu=document.getElementById("profileMenu");
  if(menu) menu.classList.remove("show");
});


function openEditProfile(){
  document.getElementById("editModal").style.display="flex";

  document.getElementById("editName").value =
  localStorage.getItem("userName") || "";

  document.getElementById("editEmail").value =
  localStorage.getItem("userEmail") || "";
}

function closeEdit(){
  document.getElementById("editModal").style.display="none";
}


function saveProfile(){

  const name=document.getElementById("editName").value;
  const email=document.getElementById("editEmail").value;
  const file=document.getElementById("editPhoto").files[0];

  localStorage.setItem("userName",name);
  localStorage.setItem("userEmail",email);

  if(file){
    const reader=new FileReader();

    reader.onload=function(e){
      localStorage.setItem("userPhoto", e.target.result);
      location.reload();
    }

    reader.readAsDataURL(file);
  }else{
    location.reload();
  }
}

function toggleChat(){
  const box=document.getElementById("chatBox");
  box.style.display = box.style.display==="flex" ? "none":"flex";
}

function sendMessage(){
  const input=document.getElementById("chatInput");
  const msg=input.value.trim();

  if(!msg) return;

  const area=document.getElementById("chatMessages");

  area.innerHTML += `<p class="user">${msg}</p>`;

  input.value="";
  area.scrollTop=area.scrollHeight;
}


