<?php

namespace Query;

use Strata\Data\Helper\GraphQLTestCase;
use Strata\Data\Http\GraphQL;
use Strata\Data\Query\BuildQuery\BuildGraphQLQuery;
use Strata\Data\Query\GraphQLQuery;

class GraphQLQueryTest extends GraphQLTestCase
{
    public function testGenerateGraphQL()
    {
        $expected = <<<EOD
query EntriesQuery {
  entries(section: "news", page: 2) { 
    title
    excerpt
    datePublished
    author
  }
}
EOD;

        $query = new GraphQLQuery();
        $query->setName('entries');
        $query->setParams(['section' => 'news', 'page' => 2]);
        $query->setFields(['title', 'excerpt', 'datePublished', 'author']);

        $builder = new BuildGraphQLQuery(new GraphQL('https://example.com/'));

        $this->assertGraphQLEquals($expected, $builder->buildGraphQL($query));
    }

    public function testFragments()
    {
        $expected = <<<EOD
query EntriesQuery {
  myAlias: entries(section: "news", page: 2) { 
    title
    excerpt
    datePublished
    author
    ...components
  }
}

fragment components on BlockComponents {
  title
  block
  something_else
}
EOD;
        $fragment = <<<EOD
title
block
something_else
EOD;

        $query = new GraphQLQuery();
        $query->setName('entries')
            ->setParams(['section' => 'news', 'page' => 2])
            ->setAlias('myAlias')
            ->setFields(['title', 'excerpt', 'datePublished', 'author', '...components'])
            ->addFragment('components', 'BlockComponents', $fragment);
        ;

        $builder = new BuildGraphQLQuery(new GraphQL('https://example.com/'));

        $this->assertGraphQLEquals($expected, $builder->buildGraphQL($query));
    }
}
