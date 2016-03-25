indexer {
    mem_limit	= 256M
}

searchd
{
	listen		= $SPHINX_HOST:$SPHINX_PORT
	log		= ${SPHINX_DIR}/${SPHINX_NAME}/searchd.log
	query_log	= ${SPHINX_DIR}/${SPHINX_NAME}/query.log
	read_timeout	= 5
	max_children	= 30
	pid_file	= ${SPHINX_DIR}/${SPHINX_NAME}/searchd.pid
	max_matches	= 1000
}


######### MAIN CHGK DATABASE ####################

source src_parent
{
    type		= mysql
    sql_host		= ${DB_HOSTNAME}
    sql_user		= $DB_USERNAME
    sql_pass		= $DB_USERPASS

    sql_query_pre	= SET NAMES utf8
}

source src_${SPHINX_NAME}_questions:src_parent
{
    sql_db		= $DB_CHGK_NAME
    sql_query		= SELECT QuestionId, Question, Answer, PassCriteria, Authors, Sources, Comments,  Questions.Type, TypeNum, Questions.Complexity, UNIX_TIMESTAMP(Tournaments.PlayedAt) playDate FROM Questions LEFT JOIN Tournaments ON Questions.ParentId=Tournaments.Id
    sql_attr_str2ordinal     = Type
    sql_attr_uint            = TypeNum
    sql_attr_uint            = Complexity
    sql_attr_timestamp = playDate
    sql_attr_multi = uint author_id from query; SELECT Question, id  FROM P2Q INNER JOIN People ON People.CharId=P2Q.Author
}

source src_${SPHINX_NAME}_tournaments:src_parent
{
    sql_db		= $DB_CHGK_NAME
    sql_query		= SELECT Id, Title, UNIX_TIMESTAMP(PlayedAt) as playDate FROM Tournaments -- WHERE Type='Ч'
    sql_attr_timestamp = playDate
}

index ${SPHINX_NAME}_tournaments
{
	source		= src_${SPHINX_NAME}_tournaments
	path		= ${SPHINX_DIR}/${SPHINX_NAME}/data/tournaments
	docinfo		= extern
	morphology	= stem_ru
	min_word_len	= 1
	min_infix_len = 1
	enable_star = 1
	expand_keywords = 1
	charset_type	= utf-8
	charset_table	= 0..9, A..Z->a..z, a..z, \
		U+C0->a, U+C1->a, U+C2->a, U+C3->a, U+C4->a, U+C5->a, U+C6->a, \
		U+C7->c,U+E7->c, U+C8->e, U+C9->e, U+CA->e, U+CB->e, U+CC->i, \
		U+CD->i, U+CE->i, U+CF->i, U+D0->d, U+D1->n, U+D2->o, U+D3->o, \
		U+D4->o, U+D5->o, U+D6->o, U+D8->o, U+D9->u, U+DA->u, U+DB->u, \
		U+DC->u, U+DD->y, U+DE->t, U+DF->s, \
		U+E0->a, U+E1->a, U+E2->a, U+E3->a, U+E4->a, U+E5->a, U+E6->a, \
		U+E7->c,U+E7->c, U+E8->e, U+E9->e, U+EA->e, U+EB->e, U+EC->i, \
		U+ED->i, U+EE->i, U+EF->i, U+F0->d, U+F1->n, U+F2->o, U+F3->o, \
		U+F4->o, U+F5->o, U+F6->o, U+F8->o, U+F9->u, U+FA->u, U+FB->u, \
		U+FC->u, U+FD->y, U+FE->t, U+FF->s, U+410..U+42F->U+430..U+44F, U+430..U+44F
}

index ${SPHINX_NAME}_questions
{
	source		= src_${SPHINX_NAME}_questions
	path		= ${SPHINX_DIR}/${SPHINX_NAME}/data/questions
	docinfo		= extern
	index_exact_words = 1;
	morphology	= stem_en, stem_ru
	#stopwords	= ${SPHINX_DATA_DIR}/stopwords.txt
	min_word_len	= 1
	min_infix_len = 1
	enable_star = 1
	expand_keywords = 1
	charset_type	= utf-8
	charset_table	= 0..9, A..Z->a..z, a..z, \
		U+C0->a, U+C1->a, U+C2->a, U+C3->a, U+C4->a, U+C5->a, U+C6->a, \
		U+C7->c,U+E7->c, U+C8->e, U+C9->e, U+CA->e, U+CB->e, U+CC->i, \
		U+CD->i, U+CE->i, U+CF->i, U+D0->d, U+D1->n, U+D2->o, U+D3->o, \
		U+D4->o, U+D5->o, U+D6->o, U+D8->o, U+D9->u, U+DA->u, U+DB->u, \
		U+DC->u, U+DD->y, U+DE->t, U+DF->s, \
		U+E0->a, U+E1->a, U+E2->a, U+E3->a, U+E4->a, U+E5->a, U+E6->a, \
		U+E7->c,U+E7->c, U+E8->e, U+E9->e, U+EA->e, U+EB->e, U+EC->i, \
		U+ED->i, U+EE->i, U+EF->i, U+F0->d, U+F1->n, U+F2->o, U+F3->o, \
		U+F4->o, U+F5->o, U+F6->o, U+F8->o, U+F9->u, U+FA->u, U+FB->u, \
		U+FC->u, U+FD->y, U+FE->t, U+FF->s, U+410..U+42F->U+430..U+44F, U+430..U+44F
}


#### DRUPAL DATABASE

source src_${SPHINX_NAME}_unsorted:src_parent
{
    sql_db		= $DB_CHGK_DRUPAL_NAME
    sql_query_pre	= SET NAMES utf8
    sql_query		= SELECT n.nid, n.title, v.body FROM node n LEFT JOIN unsorted u ON u.nid = n.nid LEFT JOIN node_revisions v ON (n.vid=v.vid)  WHERE n.type='unsorted' AND n.status=1 AND u.archived=0
}


index ${SPHINX_NAME}_unsorted
{
	source		= src_${SPHINX_NAME}_unsorted
	path		= ${SPHINX_DIR}/${SPHINX_NAME}/data/unsorted
	docinfo		= extern
	morphology	= stem_ru
	min_word_len	= 1
	min_infix_len = 1
	enable_star = 1
	expand_keywords = 1
	charset_type	= utf-8
}


