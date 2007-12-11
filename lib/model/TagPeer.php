<?php
/*
 * This file is part of the sfPropelActAsTaggableBehavior package.
 * 
 * (c) 2007 Xavier Lacot <xavier@lacot.org>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Subclass for performing query and update operations on the 'tag' table.
 * 
 * @package plugins.sfPropelActAsTaggableBehaviorPlugin.lib.model
 */ 
class TagPeer extends BaseTagPeer
{
  /**
   * Returns all tags, eventually with a limit option.
   * The first optionnal parameter permits to add some restrictions on the 
   * objects the selected tags are related to.
   * The second optionnal parameter permits to restrict the tag selection with
   * different criterias
   * 
   * @param      Criteria    $c
   * @param      array       $options
   * @return     array
   */
  public static function getAll(Criteria $c = null, $options = array())
  {
    if ($c == null)
    {
      $c = new Criteria();
    }

    if (isset($options['limit']))
    {
      $c->setLimit($options['limit']);
    }

    if (isset($options['like']))
    {
      $c->add(TagPeer::NAME, $options['like'], Criteria::LIKE);
    }

    if (isset($options['triple']))
    {
      $c->add(TagPeer::IS_TRIPLE, $options['triple']);
    }

    if (isset($options['namespace']))
    {
      $c->add(TagPeer::TRIPLE_NAMESPACE, $options['namespace']);
    }

    if (isset($options['key']))
    {
      $c->add(TagPeer::TRIPLE_KEY, $options['key']);
    }

    if (isset($options['value']))
    {
      $c->add(TagPeer::TRIPLE_VALUE, $options['value']);
    }

    return TagPeer::doSelect($c);
  }

  /**
   * Returns all tags, sorted by name, with their number of occurencies.
   * The first optionnal parameter permits to add some restrictions on the 
   * objects the selected tags are related to.
   * The second optionnal parameter permits to restrict the tag selection with
   * different criterias
   * 
   * @param      Criteria    $c
   * @param      array       $options
   * @return     array
   */
  public static function getAllWithCount(Criteria $c = null, $options = array())
  {
    $tags = array();

    if ($c == null)
    {
      $c = new Criteria();
    }

    if (isset($options['model']))
    {
      $c->add(TaggingPeer::TAGGABLE_MODEL, $options['model']);
    }

    if (isset($options['like']))
    {
      $c->add(TagPeer::NAME, $options['like'], Criteria::LIKE);
    }

    if (isset($options['triple']))
    {
      $c->add(TagPeer::IS_TRIPLE, $options['triple']);
    }

    if (isset($options['namespace']))
    {
      $c->add(TagPeer::TRIPLE_NAMESPACE, $options['namespace']);
    }

    if (isset($options['key']))
    {
      $c->add(TagPeer::TRIPLE_KEY, $options['key']);
    }

    if (isset($options['value']))
    {
      $c->add(TagPeer::TRIPLE_VALUE, $options['value']);
    }

    $c->addSelectColumn(TagPeer::NAME);
    $c->addSelectColumn(TaggingPeer::COUNT);
    $c->addJoin(TagPeer::ID, TaggingPeer::TAG_ID);
    $c->addGroupByColumn(TaggingPeer::TAG_ID);
    $c->addDescendingOrderByColumn(TaggingPeer::COUNT);
    $c->addAscendingOrderByColumn(TagPeer::NAME);
    $rs = TagPeer::doSelectRS($c);

    while ($rs->next())
    {
      $tags[$rs->getString(1)] = $rs->getInt(2);
    }

    ksort($tags);
    return $tags;
  }

  /**
   * Returns the names of the models that have instances tagged with one or 
   * several tags. The optionnal parameter might be a string, an array, or a 
   * comma separated string
   * 
   * @param      mixed       $tags
   * @return     array
   */
  public static function getModelsTaggedWith($tags = array())
  {
    if (is_string($tags))
    {
      if (false !== strpos($tags, ','))
      {
        $tags = explode(',', $tags);
      }
      else
      {
        $tags = array($tags);
      }
    }

    $c = new Criteria();
    $c->addJoin(TagPeer::ID, TaggingPeer::TAG_ID);
    $c->add(TagPeer::NAME, $tags, Criteria::IN);
    $c->addGroupByColumn(TaggingPeer::TAGGABLE_ID);
    $having = $c->getNewCriterion(TagPeer::COUNT, count($tags), Criteria::GREATER_EQUAL);
    $c->addHaving($having);
    $c->clearSelectColumns();
    $c->addSelectColumn(TaggingPeer::TAGGABLE_MODEL);
    $c->addSelectColumn(TaggingPeer::TAGGABLE_ID);

    $sql = BasePeer::createSelectSql($c, array());
    $con = Propel::getConnection();
    $stmt = $con->prepareStatement($sql);
    $position = 1;

    foreach ($tags as $tag)
    {
      $stmt->setString($position, $tag);
      $position++;
    }

    $stmt->setString($position, count($tags));
    $rs = $stmt->executeQuery(ResultSet::FETCHMODE_NUM);
    $models = array();

    while ($rs->next())
    {
      $models[] = $rs->getString(1);
    }

    return $models;
  }

  /**
   * Returns the most popular tags with their associated weight. See 
   * sfPropelActAsTaggableToolkit::normalize for more details.
   * 
   * The first optionnal parameter permits to add some restrictions on the 
   * objects the selected tags are related to.
   * The second optionnal parameter permits to restrict the tag selection with
   * different criterias
   * 
   * @param      Criteria    $c
   * @param      array       $options
   * @return     array
   */
  public static function getPopulars($c = null, $options = array())
  {
    if ($c == null)
    {
      $c = new Criteria();
    }

    if (!$c->getLimit())
    {
      $c->setLimit(sfConfig::get('app_tags_limit', 100));
    }

    $all_tags = TagPeer::getAllWithCount($c, $options);
    return sfPropelActAsTaggableToolkit::normalize($all_tags);
  }

  /**
   * Returns the tags that are related to one or more other tags, with their 
   * associated weight (see sfPropelActAsTaggableToolkit::normalize for more 
   * details).
   * The "related tags" of one tag are the ones which have at least one 
   * taggable object in common.
   * 
   * The first optionnal parameter permits to add some restrictions on the 
   * objects the selected tags are related to.
   * The second optionnal parameter permits to restrict the tag selection with
   * different criterias
   * 
   * @param      mixed       $tags
   * @param      array       $options
   * @return     array
   */
  public static function getRelatedTags($tags = array(), $options = array())
  {
    $tags = sfPropelActAsTaggableToolkit::explodeTagString($tags);

    if (is_string($tags))
    {
      $tags = array($tags);
    }

    $tagging_options = $options;

    if (isset($tagging_options['limit']))
    {
      unset($tagging_options['limit']);
    }

    $taggings = self::getTaggings($tags, $tagging_options);
    $result = array();

    foreach ($taggings as $key => $tagging)
    {
      $c = new Criteria();
      $c->add(TagPeer::NAME, $tags, Criteria::NOT_IN);
      $c->add(TaggingPeer::TAGGABLE_ID, $tagging, Criteria::IN);
      $c->add(TaggingPeer::TAGGABLE_MODEL, $key);
      $c->addJoin(TaggingPeer::TAG_ID, TagPeer::ID);
      $tags = TagPeer::doSelect($c);

      foreach ($tags as $tag)
      {
        if (!isset($result[$tag->getName()]))
        {
          $result[$tag->getName()] = 0;
        }

        $result[$tag->getName()]++;
      }
    }

    if (isset($options['limit']))
    {
      arsort($result);
      $result = array_slice($result, 0, $options['limit'], true);
    }

    ksort($result);
    return sfPropelActAsTaggableToolkit::normalize($result);
  }

  /**
   * Retrieves the objects tagged with one or several tags.
   * 
   * The second optionnal parameter permits to restrict the tag selection with
   * different criterias
   * 
   * @param      mixed       $tags
   * @param      array       $options
   * @return     array
   */
  public static function getTaggedWith($tags = array(), $options = array())
  {
    $taggings = self::getTaggings($tags, $options);
    $result = array();

    foreach ($taggings as $key => $tagging)
    {
      $c = new Criteria();
      $peer = get_class(call_user_func(array(new $key, 'getPeer')));
      $objects = call_user_func(array($peer, 'retrieveByPKs'), $tagging);

      foreach ($objects as $object)
      {
        $result[] = $object;
      }
    }

    return $result;
  }

  /**
   * Returns the taggings associated to one tag or a set of tags.
   * 
   * The second optionnal parameter permits to restrict the results with
   * different criterias
   * 
   * @param      mixed       $tags
   * @param      array       $options
   * @return     array
   */
  private static function getTaggings($tags = array(), $options = array())
  {
    $tags = sfPropelActAsTaggableToolkit::explodeTagString($tags);

    if (is_string($tags))
    {
      $tags = array($tags);
    }

    $c = new Criteria();
    $c->addJoin(TagPeer::ID, TaggingPeer::TAG_ID);
    $c->add(TagPeer::NAME, $tags, Criteria::IN);
    $c->addGroupByColumn(TaggingPeer::TAGGABLE_ID);
    $having = $c->getNewCriterion(TagPeer::COUNT, count($tags), Criteria::GREATER_EQUAL);
    $c->addHaving($having);
    $c->clearSelectColumns();
    $c->addSelectColumn(TaggingPeer::TAGGABLE_MODEL);
    $c->addSelectColumn(TaggingPeer::TAGGABLE_ID);

    if (isset($options['model']))
    {
      $c->add(TaggingPeer::TAGGABLE_MODEL, $options['model']);
    }
    else
    {
      $c->addGroupByColumn(TaggingPeer::TAGGABLE_MODEL);
    }

    if (isset($options['triple']))
    {
      $c->add(TagPeer::IS_TRIPLE, $options['triple']);
    }

    if (isset($options['namespace']))
    {
      $c->add(TagPeer::TRIPLE_NAMESPACE, $options['namespace']);
    }

    if (isset($options['key']))
    {
      $c->add(TagPeer::TRIPLE_KEY, $options['key']);
    }

    if (isset($options['value']))
    {
      $c->add(TagPeer::TRIPLE_VALUE, $options['value']);
    }

    $param = array();
    $sql = BasePeer::createSelectSql($c, $param);
    $con = Propel::getConnection();
    $stmt = $con->prepareStatement($sql);
    $position = 1;

    foreach ($tags as $tag)
    {
      $stmt->setString($position, $tag);
      $position++;
    }

    if (isset($options['model']))
    {
      $stmt->setString($position++, $options['model']);
    }

    $stmt->setString($position, count($tags));
    $rs = $stmt->executeQuery(ResultSet::FETCHMODE_NUM);
    $taggings = array();

    while ($rs->next())
    {
      $model = $rs->getString(1);

      if (!isset($taggings[$model]))
      {
        $taggings[$model] = array();
      }

      $taggings[$model][] = $rs->getInt(2);
    }

    return $taggings;
  }

  /**
   * Retrives a tag by his name.
   * 
   * @param      String      $tagname
   * @return     Tag
   */
  public static function retrieveByTagname($tagname)
  {
    $c = new Criteria();
    $c->add(TagPeer::NAME, $tagname);
    return TagPeer::doSelectOne($c);
  }

  /**
   * Retrieves a tag by his name. If it does not exist, creates it (but does not
   * save it)
   * 
   * @param      String      $tagname
   * @return     Tag
   */
  public static function retrieveOrCreateByTagname($tagname)
  {
    // retrieve or create the tag
    $tag = TagPeer::retrieveByTagName($tagname);

    if (!$tag)
    {
      $tag = new Tag();
      $tag->setName($tagname);
      $triple = sfPropelActAsTaggableToolkit::extractTriple($tagname);
      list($tagname, $triple_namespace, $triple_key, $triple_value) = $triple;
      $tag->setTripleNamespace($triple_namespace);
      $tag->setTripleKey($triple_key);
      $tag->setTripleValue($triple_value);
      $tag->setIsTriple(!is_null($triple_namespace));
    }

    return $tag;
  }
}