<?php

class DbPeople {
    private $db;
    private $years;
    
    public function __construct() {
        $this->db = new DbDatabase();
    }
    private function loadPeople() {
       $this->people = array();
        $res = $this->db->getAllEditorsRes( array('sortby' => 'tour_number') );
        while ($line = db_fetch_object($res)) {
            $this->people[] = DbPerson::newFromRow($line);
        }
    }

    private function isFilterCE() {
        $this->parseUrl();
        return in_array( 'ce', $this->pathArray );
    }

    private function isFilterCR() {
        $this->parseUrl();
        return in_array( 'cr', $this->pathArray );
    }

    private function isFilterEditor() {
        $this->parseUrl();
        return in_array( 'editors', $this->pathArray );
    }

    private function isFilterRegistered() {
        $this->parseUrl();
        return in_array( 'registered', $this->pathArray );
    }
    
    private function parseUrl() {
      if ( $this->urlIsParsed ) return;
      $path = explode('/', $_GET['q']);
      $page = array_shift($path);
      $this->pathArray = $path;
    }
    
    private function getFilterSearch() {
        $this->parseUrl();
        $l = sizeof($this->pathArray);
        if ( $l < 2 || $this->pathArray[$l-2] !='search' ) return;
        return $this->pathArray[$l-1];
    }
    
    
    public function getFilterForm() {
      $this->parseUrl();
      $filters = user_filters();
      $form = array();
#      $form['#method'] = 'GET';
#      $form['#pre_render'][] = 'chgk_db_form_clean';
      $form['filters'] = array(
        '#type' => 'fieldset',
        '#title' => t('Показывать только:'),
#        '#theme' => 'user_filters',
      );
      


      $form['filters']['search'] = array(
        '#type' => 'textfield',
        '#title' => 'Искать',
        '#default_value' =>  $this->getFilterSearch(),
        '#size' => 20
      );

      $form['filters']['editor'] = array(
        '#type' => 'checkbox',
        '#title' => 'Редакторов',
        '#default_value' =>  $this->isFilterEditor()
      );

      $form['filters']['ce'] = array(
        '#type' => 'checkbox',
        '#title' => 'Сертифицированных редакторов',
        '#default_value' =>  $this->isFilterCE()
      );
      
      $form['filters']['cr'] = array(
        '#type' => 'checkbox',
        '#title' => 'Сертифицированных арбитров',
        '#default_value' =>  $this->isFilterCR()
      );

      $form['filters']['registered'] = array(
        '#type' => 'checkbox',
        '#title' => 'Зарегистрированных на сайте',
        '#default_value' =>  $this->isFilterRegistered()
      );

      
      $form['filters']['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Фильтровать'
      );
      
      drupal_add_js('misc/form.js', 'core');

      return $form;
    }

    public function getAllHtml() {


#        $this->loadPeople();
        $output = '';
        $output .=drupal_get_form('chgk_db_people_filter_form');
        $output.=$this->getTableHtml();
/*                $output .= '<table><tr><th>Фамилия, имя</th><th>Количество турниров</th><th>Количество вопросов</th></tr>'."\n";
        $i=1;
        foreach ($this->people as $person) {
#          $output.=get_tr(        <td>".$person->getLink()."</td><td>".$person->getToursNumber()."</td><td>".$person->getQuestionsNumber()
        }
        $output.="</table>";*/
        
        return $output;
    }
    
    private function getTableHtml() {
      global $pager_total_items;
      $filter = $this->build_filter_query();
      $header = array(
        array('data' => '&#8470;'),
        array('data' => 'Фамилия, имя', 'field' => 'p.surname'),
        array('data' => 'Турниров', 'field' => 'p.TNumber', 'sort'=>'desc'),
        array('data' => 'Вопросов', 'field' => 'p.QNumber'),
        array('data' => 'Сертификация', 'field' => 'p.QNumber'),
        array('data' => 'Ник на сайте')
      );
      $sql = "SELECT  p.* from {People} p {$filter['join']} WHERE 1 {$filter['where']} GROUP BY CharId";
      $sql .= tablesort_sql($header);
      $sql = preg_replace('/(p\.surname\s+(A|DE)SC)/','$1, p.name $2SC', $sql);
      

      $query_count = "SELECT count(DISTINCT CharId) from  {People} p {$filter['join']} WHERE 1 {$filter['where']}";
      $result = pager_query($sql, 50, 0, $query_count, $filter['args']);
      $rows = array();
      $output .= '<p>'.$pager_total_items[0].' человек'.$this->suffix($pager_total_items[0]).'</p>';
      $n = isset($_GET['page']) ? $_GET['page'] * 50:0;
      while ($line = db_fetch_object($result)) {
        $n++;
        $person = DbPerson::newFromRow($line);
        $cert = array();
        if ( $person->isCertifiedEditor() ) $cert[] = 'редактор';
        if ( $person->isCertifiedReferee() ) $cert[] = 'арбитр';
        $cert = $cert?implode(', ', $cert):'&mdash;';
        $nick = $person->getNick() or $nick = '&mdash;';
        $rows[] = array( $n, $person->getLink(), $person->getToursNumber(), $person->getQuestionsNumber(), $cert, $nick );
      }
      $output .= theme('table', $header, $rows);
      $output .= theme('pager', NULL, 50, 0);

      return $output;
      

/*    $accounts[$account->uid] = '';
    $form['name'][$account->uid] = array('#value' => theme('username', $account));
    $form['status'][$account->uid] =  array('#value' => $status[$account->status]);
    $users_roles = array();
    $roles_result = db_query('SELECT rid FROM {users_roles} WHERE uid = %d', $account->uid);
    while ($user_role = db_fetch_object($roles_result)) {
      $users_roles[] = $roles[$user_role->rid];
    }
    asort($users_roles);
    $form['roles'][$account->uid][0] = array('#value' => theme('item_list', $users_roles));
    $form['member_for'][$account->uid] = array('#value' => format_interval(time() - $account->created));
    $form['last_access'][$account->uid] =  array('#value' => $account->access ? t('@time ago', array('@time' => format_interval(time() - $account->access))) : t('never'));
    $form['operations'][$account->uid] = array('#value' => l(t('edit'), "user/$account->uid/edit", array('query' => $destination)));
  }*/

  }

  private function suffix($n) {
    if (  $n%10 >= 2 &&  $n%10 <= 4 || ($n<20 && $n>10) ) {
	return 'а';
    } else return '';
  }

  public function filterFormSubmit($form, &$form_state) {
    $this->form = $form;
    $this->formState = &$form_state;
    $this->parsePost();
    $this->redirect();
  }
  
  public function redirect() {
    $url = 'people';

    if ( $this->filterValues['editor'] ) {
      $url.="/editors";
    }


    if ( $this->filterValues['ce'] ) {
      $url.="/ce";
    }
    
    if ( $this->filterValues['cr'] ) {
      $url.="/cr";
    }

    if ( $this->filterValues['registered'] ) {
      $url.="/registered";
    }

    if ( $this->filterValues['search'] ) {
      $url.="/search/".$this->filterValues['search'];
    }
    


    drupal_redirect_form( $this->form, $url );
  }

  private function parsePost() {
    $this->filterValues = $this->formState['values'];
  }
    
  private function build_filter_query() {
    $filters = user_filters();
    $where = $args = $join = array();
    $where[] = 'CharId  IS NOT NULL';
    if ( $this->isFilterEditor() ) {
      $join[] = 'INNER JOIN {P2T} p2t ON  p2t.Author=p.CharId';
    }
    if ( $this->isFilterCE() ) {
      $where[] = 'p.IsCertifiedEditor=1';
    }

    if ( $this->isFilterCR() ) {
      $where[] = 'p.IsCertifiedReferee=1';
    }

    if ( $this->isFilterRegistered() ) {
      $join[] = 'INNER JOIN {profile_fields} f ON f.name = \'profile_charid\'';
      $join[] = 'INNER JOIN {profile_values} v ON f.fid=v.fid  AND v.value=p.CharId';
    }

    if ( $sstr = $this->getFilterSearch() ) {
      $where[] = 'Concat(p.Name," ",p.Surname) LIKE "%%'.$sstr.'%%"';
    }


    $where = !empty($where) ? 'AND '. implode(' AND ', $where) : '';
    $join = !empty($join) ? ' '. implode(' ', array_unique($join)) : '';

    return array('where' => $where,
      'join' => $join,
      'args' => $args,
    );
  }
}
