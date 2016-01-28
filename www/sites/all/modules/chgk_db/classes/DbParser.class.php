<?php

class DbParser {
  private $questions = null;
  private $encodings = array('utf-8', 'cp1251', 'koi8-r');
  private $champPart = '';
  private $zeroTourName = '';
  private $parsed = array();
  private $lineNumber = 0;
  private $parsingTourName = FALSE;
  private $fields = array(
    'Ответ' => 'Answer',
    'О' => 'Answer',
    'Автор' => 'Authors',
    'Авторы' => 'Authors',
    'Автор(ы)' => 'Authors',
    'Зачёт' => 'PassCriteria',
    'Зачет' => 'PassCriteria',
    'А' => 'Answer',
    'Комментарий' => 'Comments',
    'Вид' => 'QType',
    'Комментарии' => 'Comments',
    'Источник' => 'Sources',
    'Источники' => 'Sources',
    'Источник(и)' => 'Sources',
    'Чемпионат' => 'Title',
    'Дата' => 'PlayedAt',
    'Инфо' => 'Info',
    'Редактор' => 'Editors',
    'Редакторы' => 'Editors'
  );
  
  private $tourInheritable = array(
    'Authors',
    'QType',
    'Editors',
    'Sources',
    'PlayedAt'
  );
  
  private $questionInheritable = array(
    'Authors',
    'QType',
    'Sources',
  );
  
  private $tourNames = array();
  
 

  
  public function __construct( $text ) {
    $this->originalText = $text;
  }

  public function getArray() {
    $this->parseIfNeeded();
    return $this->parsed;
  }

  private function parseIfNeeded() {
    if ($this->questions === null ) {
      $this->parse();
    }
  }

  private function parse() {
    $this->detectEncoding();
    $this->split();
    $this->lineNumber = 0; 
    $this->parseChampPart();
    
    $tour_number = 0;
    foreach ( $this->questions as $tourName => $tour ) {
      $tourNames[ ++$tour_number ] = $tourName;
      $oldTourName = $tourName;

      if ( $tourName === 0 && $this->zeroTourName ) {
        $tourName = $this->zeroTourName;
      }
      $t = $this->parseTourPart( $tourName );
      foreach ($this->tourInheritable as $i) {
        if ( !empty($this->parsed['tournament'][$i]) && empty($t[$i]) ) {
          $this->parsed['tours'][ $tourName ][ 'tour' ][$i] = 
            $this->parsed['tournament'][$i];
        }
      }
      foreach ( $tour as $number => $question ) {
        $this->lineNumber = $this->startLine[ $oldTourName ][ $number ][0];
        $q = $this->parseQuestion ( $question );
        $q [ 'Number' ] = $number;
        $this->parsed[ 'tours' ][ $tourName ][ 'questions' ] [ $number ] = $q;
        foreach ($this->questionInheritable as $i) {
          $i1 = $i=='QType'?'Type':$i;
          if ( !empty( $this->parsed['tours'][ $tourName ][ 'tour' ] [$i] ) && empty($q[$i1]) ) {
            $this->parsed['tours'][ $tourName ][ 'questions' ][$number][$i1] = $this->parsed['tournament'][$i];
          }
        }
      }
    }
    if ( isset($this->parsed['tours'][0]) && sizeof($this->parsed['tours']) >1 ) {
      $this->savedLineNumber = $this->startLine[0][1][0];
      $this->lineNumber =$this->startLine[0]['tour'][1];
      $this->setError('Что-то очень похожее на вопросы перед первым туром');
    }

  }

  private function parseTourPart( $tour ) {
    static $number = 0;
    $this->lineNumber = $this->startLine[ $tour ][ 'tour' ][0];
    $result = $this->findFields( $this->champPart[ $tour ], 'Info' );
    $result['Type'] = 'Т';
    $result['Title'] = $tour;
    $result['Number'] = ++$number;
    $result['QuestionsNum'] = sizeof($this->questions[$tour]);
    return $this->parsed['tours'][ $tour ][ 'tour' ] = $result;
  }
  
  
  private function parseChampPart() {
    $regexp = '/\s*\n([^\n]+):\s*$/';
    if ( 0 && preg_match ($regexp , $this->champPart[0], $matches ) ) {
      $this->zeroTourName = $matches[1];
      $this->champPart[0] = preg_replace($regexp,'', $this->champPart[0]);
    }
    $result = $this->findFields( $this->champPart[0], '' );
    $result['Type'] = 'Ч';
    if ( $this->zeroTourName && $this->questions[0] ) {
      $currentTour = $this->zeroTourName;
    } else {
      $currentTour = 0;
    }
    if ( !isset( $result['PlayedAt'] ) ) {
      $this->setError('Отсутствует дата. Формат:<br /><pre>Дата: YYYY-MM-DD</pre>');
    }
    $this->parsed['tournament'] = $result;
  }
  
  private function checkField( $text, $key, &$line ) {
    $regexp = '/^'.preg_quote($text).'[.:]\s*/';
    if ( preg_match($regexp, $line) ) {
      $field = $key;
      $result[ $field ] = '';      
      $line = preg_replace( $regexp, '', $line);
      return true;
    } else {
      return false;
    }

  }
  
  private function saveLineNumber( $shift = 0 ) {
    $this->savedLineNumber = $this->lineNumber + $shift;
  }

  private function findFields( $text, $beginField = '' ) {
    $lines = explode("\n", $text);
    $result = $beginField ? array($beginField=>''):array();
    
    $field = $beginField;
    $this->saveLineNumber( 1 );

    $pretext = false;
    foreach ( $lines as $line ) {
      $this->lineNumber++;
      if ($pretext) {
        $pretext.="$line\n";
      }
      foreach ( $this->fields as $text => $key ) {
        if ( $this->checkField( $text, $key, $line ) ) {

          if ( $field ) {
            $result[ $field ] = rtrim( $result[ $field ] );
            $this->processFieldValue( $field, $result[ $field ] );
          } elseif ( $pretext ) {print $pretext;
              $this->setError( 'Нераспознанный текст' );
          }
          $this->saveLineNumber();
          $field = $key;
          $result[ $field ] = '';
          break;
        }
      }

      if ( !$pretext && !$field && preg_match('/\S/', $line ) ) {
        $pretext = $line."\n";
        $this->saveLineNumber();
      }
      if ( !$result[ $field ] && !preg_match( '/\S/', $line ) ) continue;
      $result[ $field ] .= $line."\n";
    }
    $this->lineNumber++;
    if ( $field ) {
      $result[ $field ] = rtrim( $result[ $field ] );
      $this->processFieldValue( $field, $result[ $field ] );
    } elseif( $pretext ) {
      $this->setError( 'Нераспознанный текст' );
    }
    $this->clearSavedLineNumber();
    return $result;
  }
  
  private function clearSavedLineNumber() {
    $this ->savedLineNumber = false;
  }
  
  private function processFieldValue( $field, &$value ) {
      if ( $field == 'PlayedAt' )  {
        $d = strtotime( $value );
        if ( !$d ) {
          $this->setError('Ошибка в дате');
        }
        $value = date("Y-m-d", $d);
      }    
  }
  
  private function setError( $str ) {
    if ( $this->savedLineNumber ) {
      $this->ln1 = $this->savedLineNumber;
      $this->ln2 = $this->lineNumber - 1;
    } else {
      $this->ln1 = $this->ln2 = $this->lineNumber;
    }
    throw new DbParserException ( $str );
  }
  
  public function getErrorLines(){
    return array($this->ln1, $this->ln2);
  }
  
  private function parseQuestion( $text ) {
    $result = $this->findFields( $text,  'Question' );
    return $result;
  }

  private function split() {
    $this->lines = explode("\n", $this->text);
    $this->marked = '';
    $currentQuestion = '';
    $currentQuestionNumber = 0;
    $currentTour = 0;
    $this->lineNumber = 0;
    $this->startLine[ $currentTour ]['tour'][0] = 1;
    foreach ( $this->lines as $line ) {
      $marked_line = $line;
      $line = preg_replace('/<!--.*?-->/','',$line);
      $this->lineNumber ++;
      if ( $this->parsingTourName ) {
        $currentTour = trim($line);
        $this->startLine[ $currentTour ]['tour'][0] = $this->lineNumber-1;
        $this->parsingTourName = FALSE;
        $this->questions[ $currentTour ] = array();
      }
      elseif ( ($tourName = $this->isTourLine( $line ) ) !== FALSE ) {
        $marked_line = "#!tour:$tourName!#";
        if ( isset( $this->questions[ $tourName ] ) ) {
          $this->setError( 'Тур с названием "'.$tourName. '" уже есть' );
        }
        if ( $currentQuestion ) {
          $this->questions[ $currentTour ][ $currentQuestionNumber ] = trim($currentQuestion);
          $this->startLine[ $currentTour ][ $currentQuestionNumber ][ 1 ] = $this->lineNumber-1;
        }
        $this->startLine[ $currentTour ]['tour'][1] = $this->lineNumber-1;

        $currentTour = $tourName;
        $currentQuestionNumber = 0;
        $currentQuestion = '';
        if ( $currentTour ) {
          $this->startLine[ $currentTour ]['tour'][0] = $this->lineNumber;
          $this->questions[ $currentTour ] = array();         
        }
      } elseif ( $rest = $this->isQuestionLine( $line ) ) {
        $marked_line = "#!question:$tourName!#";
        if ( $currentQuestion ) {
          $this->questions[ $currentTour ][ $currentQuestionNumber ] = trim($currentQuestion);
        }
        if (isset($this->startLine[ $currentTour ][ $currentQuestionNumber ][ 0 ] ) ) {
          $this->startLine[ $currentTour ][ $currentQuestionNumber ][ 1 ]  = $this->lineNumber-1;
        }
        $currentQuestionNumber++;
        $this->startLine[ $currentTour ][ $currentQuestionNumber ][ 0 ]  = $this->lineNumber;
        $currentQuestion = $rest === true ? '' : $rest."\n";
      } elseif ( $currentQuestionNumber ) {
        $currentQuestion.=$line."\n";
      } else {
        $this->champPart[ $currentTour ] .= $line."\n";
      }
    }
    $this->startLine[ $currentTour ][ 'tour' ][ 1 ] = $this->lineNumber;
    $this->questions[ $currentTour ][ $currentQuestionNumber ] = $currentQuestion;
    $this->startLine[ $currentTour ][ $currentQuestionNumber ][ 1 ] = $this->lineNumber;
  }

  private function isTourLine( $line ) {
    $tourName = FALSE;
    if ( preg_match('/^\s*Тур:(.*)/', $line, $matches ) ) {
      $tourName = trim($matches[1]);
      if ( !$tourName ) { 
        $this->parsingTourName = TRUE;
        $tourName = FALSE;
      } elseif ( preg_match('/^\d+$/', $tourName ) ) {
        $tourName = 'Тур '.$tourName;
      }
    } elseif ( preg_match ('/\s*Тур ([\d\.]+?)[\.:]?\s*$/', $line, $matches ) ) {
      $tourName = 'Тур '.$matches[1];
    }  elseif ( preg_match ('/\s*(\d+)\s+тур[.\s]*/', $line, $matches ) ) {
      $tourName = 'Тур '.$matches[1];
    }
    return $tourName;
  }

  private function isQuestionLine( $line ) {
    $result = false;
    if ( preg_match( '/^\s*Вопрос\s+\d+[:.\s]*(.*)/', $line, $matches ) ) {
      $rest = trim( $matches[1] );
      $result = $rest ? $rest : true;
    }
    return $result;
  }

  private function detectEncoding() {
    foreach ($this->encodings as $encoding ) {
      $vopros = iconv( 'utf-8', $encoding, 'Вопрос' );
      if ( preg_match( "/$vopros/", $this->originalText ) ) {
        $this->encoding = $encoding;
        $this->text = iconv( $encoding, 'utf-8', $this->originalText );
        break;
      }
    }
    if ( !$this->text ) {
      throw new Exception( 'Can not deteсt file encoding' );
    }
  }
  
  public function getQuestionLine( $tour, $question ) {
    $i = 0;
    return $this->startLine[ $tour ] [ $question ];
  }
}

class DbParserException extends Exception {
  
}