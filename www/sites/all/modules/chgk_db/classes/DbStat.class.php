<?php 

class DbStat {

  public function getPage() {
    $output='';
    $output .= $this->getMonthIncrementalStat();
    $output .= $this->getDayIncStat();
    $output .= $this->getMonthNumberStat();
    $this->addJavascriptToHeader();
    return $output;
  }


  public function addJavascriptToHeader() {
    $js = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
    $js .= '<script>
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = '.json_encode( $this->data).';
        var titles = '.json_encode( $this->titles ).';
        var i;
        for ( i in data ) {
          var options = {
            title: titles[i]
          };
          var chart = new google.visualization.LineChart(document.getElementById(i));
          var d = google.visualization.arrayToDataTable(data[i]);
          chart.draw(d, options);         
        }
      }  
      ';
    
    $js .='</script>';
    drupal_set_html_head( $js );
  }

  private function setId( $id ) {
    $this->id = $id;
  }

  function resetData() {
    $this->data = array();
  }

  function getDayNumbers() {
    if ( !isset( $this->dayNumbers ) ) {
      $instance = new DbDatabase();
      $res = $instance->getLastTournamentsRes(50000);
      for ($date = $this->getFirstDate(); $date<=$this->getLastDate(); $date = date('Y-m-d', strtotime($date.' +1 days') ) ) {
        $this->dayNumbers[ $date ] = 0;
      }
 
      while ($row = $instance->fetch_row($res)) {
        $package = DbPackage::newFromRow($row);
        $this->dayNumbers[$package->getCreatedAt()]+=$package->getQuestionsNumber();
      }
    }
    return $this->dayNumbers;
  }

  function getDayNumber( $date ) {
    if ( !isset($this->dayNumbers) ){
      $this->getDayNumbers();
    }
    return $this->dayNumbers[$date];
  }

  function getFirstDate() {
      return '2000-10-01';
  }

  function getMonthNumbers() {
    $md = array();
    foreach ( $this->getDayNumbers() as $date => $number ) {
      $month = preg_replace('/-\d\d$/', '', $date);
      if (!isset($md[$month])) {
        $md[$month] = 0;
      }
      $md[$month] += $number;
    }
    return $md;
  }

  function setDataTitles( $titles ) {
    if ( $this->data[ $this->id ] ) {
      array_unshift($this->data[ $this->id ], $titles);
    } else {
      $this->data[ $this->id ] = array($titles);
    }
  }

  private function setChartTitle( $title ) {
    $this->titles[$this->id] = $title;
  }

  public function getMonthNumberStat() {
    $this->setId('month-number');
    $this->setDataTitles(array('Месяц', 'Количество вопросов'));
    $this->setChartTitle('Количество добавленных вопросов по месяцам');
    foreach ($this->getMonthNumbers() as $month=>$number) {
      if ($month == '2000-10') continue;
      $this->addData(array($month, $number));
    }
    return $this->getDiv();
  }
  
  public function getDayIncStat() {
    $this->setId('day-number');
    $this->setDataTitles(array('День', 'Количество вопросов'));
    $this->setChartTitle('Количество  вопросов за последний год');
    
    $last = date('Y-m-d', strtotime($this->getLastDate().' -1 years') );
    foreach ($this->getDayNumbers() as $day=>$number) {
      $n += $number;
      if ($day > $last ) {
        $this->addData(array($day, $n));
      }
    }
    return $this->getDiv();
  }
 
  public function getMonthIncrementalStat() {
    $this->setId('month-inc');
    $this->setDataTitles(array('Дата', 'Количество вопросов'));
    $this->setChartTitle('Количество вопросов в базе');
    $n = 0;
    foreach ($this->getMonthNumbers() as $month=>$number) {
      $n += $number;
      $this->addData(array($month, $n));
    }
    return $this->getDiv();
  }


  private function addData( $data ) {
    $this->data[$this->id][] = $data;
  }

  function getDiv( ) {
    return '<div id="'.$this->id.'" style="width: 100%; height: 500px;"></div>';
  }

  function getLastDate() {
    return date('Y-m-d');
  }
  /*$month_data1[] = "['Дата', 'Количество вопросов']";
  $month_data2[] = "['Месяц', 'Количество вопросов']";

  for ($date = $first; $date<=$last; $date = date('Y-m-d', strtotime($date.' +1 days') ) ) {
    $month = preg_replace('/-\d\d$/', '', $date);
    if (!isset($md[$month])) {
      $md[$month] = 0;
    }
    if (isset($number[$date])) {
      $n += $number[$date];
      $md[$month] += $number[$date];
    }
    $data[] = "['$date', $n]";
    if($month!='2000-10') {
      $month_data2[$month] = "['$month', $md[$month] ]";
    }
    $month_data1[$month] = "['$month', $n ]";
  }
  $js1 = '<script>
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
          var data = google.visualization.arrayToDataTable(['.
            join (",\n", $month_data1).
         ']);
          var options = {
            title: "Количество вопросов"
          };
          var chart = new google.visualization.LineChart(document.getElementById("chart_div"));
          chart.draw(data, options);
        }
      </script>';

  $js2 = '<script>
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
          var data = google.visualization.arrayToDataTable(['.
            join (",\n", $month_data2).
         ']);
          var options = {
            title: "Количество вопросов"
          };
          var chart = new google.visualization.LineChart(document.getElementById("chart_div2"));
          chart.draw(data, options);
        }
      </script>';
  drupal_set_html_head('<script type="text/javascript" src="https://www.google.com/jsapi"></script>'.
    $js1.$js2
  );

  return '<div id="chart_div" style="width: 100%; height: 500px;"></div>'
  .'<div id="chart_div2" style="width: 100%; height: 500px;"></div>';
}*/
}
