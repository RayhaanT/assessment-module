function testdata(str){
	var res = str.split("~");
	document.getElementById("companyid").value = res[0];
	document.getElementById("teamid").value = res[1];
	document.getElementById("planid").value = res[2];
	document.getElementById("accesskey").value = res[3];
}