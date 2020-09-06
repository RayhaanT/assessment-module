
function drawChart1() {
var data = google.visualization.arrayToDataTable([
    ['Task', 'Hours per Day'],
    ['Completed',     70],
    ['Incompleted',    30]
  ]);

  var options = {
    title: '',
    pieHole: 0.7,
  };

var chart = new google.visualization.PieChart(document.getElementById('donut_single1'));
chart.draw(data, options);
}


function drawChart2() {
var data = google.visualization.arrayToDataTable([
    ['Task', 'Hours per Day'],
    ['Completed',     70],
    ['Incompleted',    30]
  ]);

  var options = {
    title: '',
    pieHole: 0.7,
  };

var chart = new google.visualization.PieChart(document.getElementById('donut_single2'));
chart.draw(data, options);
}


function drawChart3() {
var data = google.visualization.arrayToDataTable([
    ['Task', 'Hours per Day'],
    ['Completed',     70],
    ['Incompleted',    30]
  ]);

  var options = {
    title: '',
    pieHole: 0.7,
  };

var chart = new google.visualization.PieChart(document.getElementById('donut_single3'));
chart.draw(data, options);
}


function drawChart4() {
var data = google.visualization.arrayToDataTable([
    ['Task', 'Hours per Day'],
    ['Completed',     70],
    ['Incompleted',    30]
  ]);

  var options = {
    title: '',
    pieHole: 0.7,
  };

var chart = new google.visualization.PieChart(document.getElementById('donut_single4'));
chart.draw(data, options);
}