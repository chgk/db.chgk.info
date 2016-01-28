<?php  


class DbDatabase  {
  const QUESTION_TABLE='Questions';
  const TOURNAMENT_TABLE='Tournaments';
  const PEOPLE_TABLE = 'People';
  const P2T_TABLE = 'P2T';
  const P2Q_TABLE = 'P2Q';
  
  private $tournamentFields = array('Id', 'Title', 'PlayedAt', 'PlayedAt2', 'Info', 'Type', 'TextId','FileName','Number','QuestionNum','ParentId');
  private $questionFields = array('QuestionId', 'ParentId', 'Number', 'Type', 'TypeNum', 'TextId', 'Question','Answer',
	'PassCriteria', 'Authors', 'Sources', 'Comments', 'Rating', 'RatingNumber', 'Complexity', 'Topic', 'ProcessedBySearch');

  public function getTournament($id) {
      if (is_numeric($id)) {
          return $this->getTournamentByDatabaseId($id);
      } else {
          return $this->getTournamentByTextId($id);
      }
  }

  public function getAllEditorsRes( $options = array() ) {
#      $res = db_query("SELECT People.* from  {P2T} LEFT JOIN {People} ON (P2T.Author=People.CharId)
#            GROUP BY CharId ORDER BY TNumber DESC");
      $orders = array();
      if ( isset ( $options[ 'sortby' ] ) ) {
        if ( $options[ 'sortby' ] == 'tour_number' ) {
          $orders[]='p.TNumber DESC';
        }
      }
      $orders[] = 'p.surname ASC';
      $orders[] = 'p.name ASC';
      $orders_str = 'ORDER BY '.implode(', ', $orders);
      $res = db_query("SELECT p.* from  {P2T} p2t LEFT JOIN {People} p ON (p2t.Author=p.CharId) GROUP BY CharId $orders_str");

      return $res;

  }
  
  public function getEditors($id) {
    $sql = "SELECT People.* from {P2T} LEFT JOIN {People} ON (P2T.Author=People.CharId)
    WHERE P2T.Tour='$id' ORDER by People.surname ASC, People.name ASC";
    $res = db_query($sql);
    $result = array();
    while ($e = db_fetch_object($res)) {
      $result[] = $e;
    }
    return $result;
  }
  
  public function addTournament( &$row ) {
    $a = (array)$row;
    $ar = array();
    foreach ($a as $k =>$v ) {
      if ( in_array( $k, $this->tournamentFields ) ) {
        $ar[$k] = $v;
      }
    } 
    $fields = implode (", ", array_keys ( $ar ) );
    foreach ( array_keys( $ar ) as $nothing ) {
      $placeholders[] = "'%s'";
    }
    $values = implode(', ', $placeholders);
    $sql = "INSERT INTO {".self::TOURNAMENT_TABLE."} ($fields) VALUES ($values)";
    $res = db_query($sql, array_values( $ar ) );
    $row->Id = $res = db_last_insert_id(self::TOURNAMENT_TABLE, 'Id');
    return $res;
  }

  public function addQuestion( $row ) {
    $a = (array)$row;
    $ar = array();
    foreach ($a as $k =>$v ) {
      if ( in_array( $k, $this->questionFields ) ) {
        $ar[$k] = $v;
      }
    } 
    $fields = implode (", ", array_keys ( $ar ) );
    foreach ( array_keys( $ar ) as $nothing ) {
      $placeholders[] = "'%s'";
    }
    $values = implode(', ', $placeholders);
    $sql = "INSERT INTO {".self::QUESTION_TABLE."} ($fields) VALUES ($values)";
    $res = db_query($sql, array_values( $ar ) );
    $res = db_last_insert_id(self::QUESTION_TABLE, 'ID');
  }

  public function updateTournament( $row ) {
    $a = (array)$row;
    $ar = array();
    foreach ($a as $k =>$v ) {
      if ( in_array( $k, $this->tournamentFields ) ) {
        $ar[$k] = $v;
      }
    } 

    $fields_array = array();
    foreach ($ar as $k=>$v) {
      $fields_array[] = "$k = '%s'";
    }
    $fields = implode (", ", $fields_array );
    $sql = "UPDATE {".self::TOURNAMENT_TABLE."} SET $fields WHERE TextId = '%s'";
    $v = array_values( $ar );
    $v[] = $row->TextId;
    $res = db_query($sql, $v);
  }

  public function deleteTournament( $textId ) {
    $sql = "DELETE FROM {".self::TOURNAMENT_TABLE."} WHERE TextId = '%s'";
    $res = db_query($sql, array( $textId ) );

  }

  public function deleteQuestion( $textId ) {
    $sql = "DELETE FROM {".self::QUESTION_TABLE."} WHERE TextId = '%s'";
    $res = db_query($sql, array( $textId ) );

  }


  public function updateQuestion( $row ) {
    $a = (array)$row;
    $ar = array();
    foreach ($a as $k =>$v ) {
      if ( in_array( $k, $this->questionFields ) ) {
        $ar[$k] = $v;
      }
    } 

    $fields_array = array();
    foreach ($ar as $k=>$v) {
      $fields_array[] = "$k = '%s'";
    }
    $fields = implode (", ", $fields_array );
    $sql = "UPDATE {".self::QUESTION_TABLE."} SET $fields WHERE TextId = '%s'";
    $v = array_values( $ar );
    $v[] = $row->TextId;
    $res = db_query($sql, $v);
  }


  public function getTournamentByDatabaseId($id){
      $sql = sprintf ("SELECT SQL_NO_CACHE t.*, count(t1.Id) as ChildrenNum FROM {%s} t
          LEFT JOIN {%s} t1 ON t1.ParentId=t.Id
      WHERE t.Id = '%d' GROUP BY t.Id", self::TOURNAMENT_TABLE, self::TOURNAMENT_TABLE, $id);
    $res = db_query($sql);
    return db_fetch_object($res);
  }
  public function getTournamentByTextId($id){
/*    if (!preg_match('/\./', $id)) {
      $id .= '.txt';
    }*/

    $id= db_escape_string ($id);
    $sql = sprintf ("SELECT SQL_NO_CACHE t.*, count(t1.Id) as ChildrenNum FROM {%s} t
        LEFT JOIN {%s} t1 ON t1.ParentId=t.Id
      WHERE t.FileName = '%s.txt' OR t.FileName = '%s' OR t.TextId = '%s' GROUP BY t.Id", 
      self::TOURNAMENT_TABLE, self::TOURNAMENT_TABLE, $id, $id, $id);
    if (@$_GET['debug'])print $sql;
    $res = db_query($sql);
    return db_fetch_object($res);
  }

  public function getQuestionByTextId($id){
    $id = db_escape_string ($id);
    $sql = sprintf ("SELECT SQL_NO_CACHE * FROM {%s} q
      WHERE  q.TextId = '%s'", 
      self::QUESTION_TABLE, $id);
    $res = db_query($sql);
    return db_fetch_object($res);
  }


  public function getPersonById($id){
    $sql = sprintf ("SELECT * FROM {%s} a
      WHERE a.CharId = '%s'",
      self::PEOPLE_TABLE, $id);
    $res = db_query($sql);
    return db_fetch_object($res);
  }

  public function getPersonsByQuestionIdRes( $qid ) {
    $sql = sprintf ("SELECT p.* FROM {%s} p INNER JOIN {%s} p2q ON p.CharId=p2q.author WHERE p2q.question=%d",
      self::PEOPLE_TABLE, self::P2Q_TABLE,$qid);
    $res = db_query($sql);
    return $res;
  }

  public function getPersonsByTournamentIdRes( $tid ) {
    $sql = sprintf ("SELECT p.* FROM {%s} p INNER JOIN {%s} p2t ON p.CharId=p2t.author WHERE p2t.Tour=%d",
        self::PEOPLE_TABLE, self::P2T_TABLE,$tid);
    if ($_GET['debug']) {
        print "!!!!".$sql;
    }
    $res = db_query($sql);
    return $res;
  }

  public function editorToursRes($id) {
    $sql = sprintf("SELECT t.* FROM {%1\$s} t
                LEFT JOIN {%2\$s} p2t ON
                (p2t.Tour = t.Id)
                WHERE p2t.Author = '%3\$s'
                ORDER BY PlayedAt DESC,  Title ",
            self::TOURNAMENT_TABLE,
            self::P2T_TABLE,
            $id);
    $res = db_query($sql);
    return $res;
  }

  public function authorQuestionsRes($id, $limit = 0) {
    $sql = sprintf("SELECT 
      t.FileName as tourFileName,
      t1.FileName as tournamentFileName,
      q.*,
      t.Id as tourId,
      t1.id as tournamentId,
      t.Title as tourTitle,
      t1.Title as tournamentTitle,
      t.Type as tourType,
      t1.Type as tournamentType
    
    
       FROM {%1\$s} q
        LEFT JOIN {%2\$s} t ON (q.ParentId=t.Id)
        LEFT JOIN {%2\$s} t1 ON (t.ParentId=t1.Id)
        LEFT JOIN {%3\$s} p2q ON
                (p2q.Question = q.QuestionId)
                WHERE p2q.Author = '%4\$s'",
            self::QUESTION_TABLE,
            self::TOURNAMENT_TABLE,
            self::P2Q_TABLE,
            $id);
    if ($limit) {
      $sql.=" LIMIT $limit";
    }
    $res = db_query($sql);
    return $res;
  }



  public function getQuestionsRes($id) {
    $sql = sprintf("SELECT * FROM {%s} WHERE ParentId=%d ORDER BY Number", self::QUESTION_TABLE, $id);
    return db_query($sql);
  }

  public function getQuestionByNumber($parentId, $number, $fromGrandFather = false) {
    $sql = 
    "SELECT SQL_NO_CACHE
      t.FileName as tourFileName,
      t1.FileName as tournamentFileName,
      q.*,
      t.Id as tourId,
      t1.id as tournamentId,
      t.Title as tourTitle,
      t1.Title as tournamentTitle,
      t.Type as tourType,
      t1.Type as tournamentType
    FROM {%1\$s} q
    LEFT JOIN {%2\$s} t ON (q.ParentId=t.Id)
    LEFT JOIN  {%2\$s} t1 ON (t.ParentId=t1.Id)
    WHERE  (".($fromGrandFather?'t':'q').".ParentId=%3\$d)  AND q.Number=%4\$d";
    if ($fromGrandFather) {
      $sql .= ' AND t.Number=1 ';
    }
    $sql = sprintf($sql, self::QUESTION_TABLE, 

    self::TOURNAMENT_TABLE,  $parentId, $number);
    $res = db_query($sql);
    $row =  $this->fetch_row($res);
     return $row;
  }
  
  public function getRandomQuestion() {
    $sql = 
    "SELECT 
      t.FileName as tourFileName,
      t1.FileName as tournamentFileName,
      q.*,
      t.Id as tourId,
      t1.id as tournamentId,
      t.Title as tourTitle,
      t1.Title as tournamentTitle,
      t.Type as tourType,
      t1.Type as tournamentType

    FROM {%1\$s} q
    LEFT JOIN {%2\$s} t ON (q.ParentId=t.Id)
    LEFT JOIN  {%2\$s} t1 ON (t.ParentId=t1.Id)
    WHERE q.Type='Ч' AND (t.PlayedAt>='2008-01-01' OR t1.PlayedAt>='2008-01-01') 
    ORDER BY rand() LIMIT 1";
 
    $res = db_query(sprintf($sql,  self::QUESTION_TABLE,
            self::TOURNAMENT_TABLE));
    return $this->fetch_row($res);
  
  }

  public function getChildrenRes($id) {
      $sql = sprintf("SELECT t.*, count(t1.Id) as ChildrenNum FROM {%s} t 
          LEFT JOIN {%s} t1 ON (t1.ParentId = t.Id) WHERE t.ParentId=%d GROUP BY t.Id 
          ORDER BY t.Number, t.PlayedAt, t.Id", self::TOURNAMENT_TABLE, self::TOURNAMENT_TABLE, $id);
    return db_query($sql);
  }
  
  public function fetch_row($res) {
    return db_fetch_object($res);
  }


  public function getTournamentsByIdsRes($ids) {
    $sql = "select SQL_NO_CACHE * FROM {%1\$s}
        WHERE Id IN (%2\$s) 
    ORDER BY FIELD(Id, %2\$s)";
    $sql = sprintf($sql,
            self::TOURNAMENT_TABLE,
            implode(',', $ids)
            );
    return db_query($sql);
  }
  
  public function getQuestionNotices( $textId ) {
    $sql = "SELECT n.nid nid, i.field_reporter_value reporter, n.created created, r.body body, r.format format,
        i.field_issue_status_value status
      FROM {node} n LEFT JOIN {content_field_issue_2q} f ON n.vid = f.vid
       LEFT JOIN {node_revisions} r ON n.vid = r.vid 
       LEFT JOIN {content_type_chgk_issue} i ON n.vid = i.vid 
       WHERE (n.type ='chgk_issue') AND (f.field_issue_2q_value = 1 ) 
       AND (n.status = 1) AND (n.title = '".db_escape_string($textId)."') ORDER BY created";
     
    $res = db_query($sql);
    $result = array();
    while ($n = db_fetch_object($res)) {
      $result[] = $n;
    }

    return $result;

  }
  
  
  public function getLastTournamentsRes($number = 10) {
    $sql = "select t1.*, (SELECT GROUP_CONCAT(Title ORDER BY Number) FROM {%1\$s} t2 WHERE t2.ParentId=t1.Id GROUP BY t2.ParentId  ) tours FROM {%1\$s} t1 WHERE t1.Type='Ч'  ORDER BY t1.CreatedAt DESC";
    $sql = sprintf($sql, self::TOURNAMENT_TABLE);
    $count_sql = sprintf("SELECT count(*) FROM {%1\$s} WHERE Type='Ч'", self::TOURNAMENT_TABLE);
    return pager_query($sql,$number, 0, $count_sql);

  }
  public function getSphinxSearchRes ($ids) {
/*    $sql="SELECT
      t.FileName as tourFileName,
      t1.FileName as tournamentFileName,
      q.*,
      t.Id as tourId,
      t1.id as tournamentId,
      t.Title as tourTitle,
      t1.Title as tournamentTitle,
      t.Type as tourType,
      t1.Type as tournamentType,
      t.PlayedAt as tourPlayedAt, 
      t.PlayedAt as tournamentPlayedAt

    FROM {%1\$s} q
    LEFT JOIN {%2\$s} t ON (q.ParentId=t.Id)
    LEFT JOIN  {%2\$s} t1 ON (t.ParentId=t1.Id)
    WHERE q.QuestionId IN (%3\$s) ";
#    ORDER BY FIELD(QuestionId, %3\$s)";
    $sql = sprintf($sql,
            self::QUESTION_TABLE,
            self::TOURNAMENT_TABLE,
            implode(',', $ids)
            );

*/

    $INNERJOIN = '';
    $first = array_shift($ids);
    $INNERJOIN = " INNER JOIN (select $first as bar";
    if ($ids) $INNERJOIN.=" UNION all SELECT ".implode(' union all select ', $ids);
    $INNERJOIN.=") as x on (q.QuestionId=x.bar)";
    $sql="SELECT SQL_NO_CACHE
      t.FileName as tourFileName,
      t1.FileName as tournamentFileName,
      q.*,
      t.Id as tourId,
      t1.id as tournamentId,
      t.Title as tourTitle,
      t1.Title as tournamentTitle,
      t.Type as tourType,
      t1.Type as tournamentType,
      t.PlayedAt as tourPlayedAt, 
      t1.PlayedAt as tournamentPlayedAt,
      t.PlayedAt2 as tourPlayedAt2, 
      t1.PlayedAt2 as tournamentPlayedAt2

    FROM {%1\$s} q
    %3\$s
    LEFT JOIN {%2\$s} t ON (q.ParentId=t.Id)
    LEFT JOIN  {%2\$s} t1 ON (t.ParentId=t1.Id)";

    $sql = sprintf($sql,
            self::QUESTION_TABLE,
            self::TOURNAMENT_TABLE,
            $INNERJOIN
            );

    
    


    return db_query($sql);
    
    return array();
    /*
    my @result =  map {$_->{doc}} @{$results->{matches}} ;
    $totalfound = $results->{total_found};
    @searchwords = keys %{$results->{words}};
    $_ = encode('koi8r', $_) foreach @searchwords;
    */
    return @result;

  }

  public function getFulltextSearchRes( $sstr, $options = array()) {
    return $this->getSphinxSearchRes( $sstr );
    global $pager_page_array, $pager_total, $pager_total_items;
    $limit = 10;
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $pager_page_array = array($page);


    $sql="SELECT
      t.FileName as tourFileName,
      t1.FileName as tournamentFileName,
      q.*,
      t.Id as tourId,
      t1.id as tournamentId,
      t.Title as tourTitle,
      t1.Title as tournamentTitle,
      t.Type as tourType,
      t1.Type as tournamentType

    FROM {%1\$s} q
    LEFT JOIN {%2\$s} t ON (q.ParentId=t.Id)
    LEFT JOIN  {%2\$s} t1 ON (t.ParentId=t1.Id)
    WHERE
     MATCH (Question,Answer,PassCriteria,Comments) AGAINST ('%3\$s' IN BOOLEAN MODE)
    ORDER BY MATCH (Question,Answer,PassCriteria,Comments) AGAINST
     ('%3\$s' IN BOOLEAN MODE) DESC LIMIT %4\$s, %5\$s";

    
    $sql = sprintf($sql,
            self::QUESTION_TABLE,
            self::TOURNAMENT_TABLE,
            $sstr,$pager_page_array[0] * $limit,
            $limit );
    $sqlcount = preg_replace('/ORDER BY.*$/s', '',$sql);
    $sqlcount = preg_replace('/SELECT.*FROM/s', 'SELECT count(*) FROM',$sqlcount);

    $count = db_result(db_query($sqlcount));

    return db_query($sql);    
  }

}

