<?php

namespace ArticleBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use ArticleBundle\Entity\Article;
use ArticleBundle\Entity\Tag;
use ArticleBundle\Entity\Author;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends Controller
{

    const ARTICLE_TYPE_SIMPLE = 1;
    const ARTICLE_TYPE_AUTHOR = 2;

    const ARTICLE_TYPES = [
        self::ARTICLE_TYPE_SIMPLE => 'Обычная статья',
        self::ARTICLE_TYPE_AUTHOR => 'Авторская статья',
    ];

    const MAX_TAGS_IN_ARTICLE = 3;

    /**
     * @Route( "/", name="article.index" )
     */
    public function indexAction()
    {

        $articles = $this->getDoctrine()
                         ->getRepository('ArticleBundle:Article' )
                         ->findAll();

        return $this->render('ArticleBundle:Default:index.html.twig', [
            'articles' => $articles
        ]);
    }

    /**
     * @Route( "/detail/{url}", name="article.detail" )
     */
    public function showAction( $url )
    {

        $article = $this->getDoctrine()
                         ->getRepository('ArticleBundle:Article' )
                         ->findOneBy([
                             'url' => $url
                         ]);

        return $this->render('ArticleBundle:Default:show.html.twig', [
            'article' => $article
        ]);
    }

    /**
     * @Route( "/tag/{tag}", name="article.by_tag" )
     */
    public function byTagAction( $tag )
    {

        $tagEntity = $this->getDoctrine()
                         ->getRepository('ArticleBundle:Tag' )
                         ->findOneBy([
                             'name' => $tag
                         ]);

        return $this->render('ArticleBundle:Default:index.html.twig', [
            'articles' => empty( $tagEntity ) ? [] : $tagEntity->getArticles()
        ]);
    }

    /**
     * @Route( "/authors-artcles", name="article.authors" )
     */
    public function listAuthorsArticlesAction()
    {

        $articles = $this->getDoctrine()
                         ->getRepository('ArticleBundle:Article' )
                         ->findBy([
                             'type' => self::ARTICLE_TYPE_AUTHOR
                         ]);

        return $this->render('ArticleBundle:Default:index.html.twig', [
            'articles' => $articles
        ]);
    }


    /**
     * @Route( "/edit/{id}", name="article.edit", defaults={"id" = null} )
     */
    public function editAction( $id )
    {

        $form = $this->_buildArticleForm( $id );

        return $this->render('ArticleBundle:Default:create.html.twig', array(
            'form' => $form->createView(),
            'errors' => [],
        ));
    }

    /**
     * @Route( "/store/{id}", name="article.store", defaults={"id" = null} )
     */
    public function storeAction( Request $request, $id )
    {
        $form = $this->_buildArticleForm( $id );
        $form->handleRequest( $request );

        if( $request->getMethod() == 'POST' && $form->isSubmitted() && $form->isValid() ){

            $article = $form->getData();
            $errors = [];
            $em = $this->getDoctrine()->getManager();

            switch( $article->getType() ){

                case self::ARTICLE_TYPE_SIMPLE:

                    $tags = $this->_prepareTags( $request->request->get('form')['tags'] );
                    if( count($tags) > self::MAX_TAGS_IN_ARTICLE ){
                        $errors[] = 'Тэгов у статьи не может быть больше ' . self::MAX_TAGS_IN_ARTICLE;
                    }

                    $article->setTags( $tags );

                    if( is_a( $article->getAuthor(), Author::class ) ){
                        $em->remove( $article->getAuthor() );
                    }

                    $article->setAuthor( [] );
                    break;

                case self::ARTICLE_TYPE_AUTHOR:

                    $article->setTags( [] );
                    $values = $request->request->get('form' );

                    if( empty($values['author']) ){
                        $errors[] = 'Укажите автора';
                    }

                    if( empty($values['author_site']) ){
                        $errors[] = 'Укажите сайт';
                    }

                    $article->setAuthor( $this->_prepareAuthor($values['author'], $values['author_site'], $article) );
                    break;
            }

            if( !empty($errors) ){
                return $this->render('ArticleBundle:Default:create.html.twig', array(
                    'form' => $form->createView(),
                    'errors' => $errors,
                ));
            }

            $currentTime = new \DateTime( 'now' );
            if( empty($article->getId()) ){
                $article->setCreatedAt( $currentTime );
            }

            $article->setUpdatedAt( $currentTime );

            $em->persist($article);
            $em->flush();
        }

        return $this->redirectToRoute('article.index' );
    }

    private function _buildArticleForm( $id )
    {

        if( !empty($id) ){
            $article = $this->getDoctrine()
                            ->getRepository('ArticleBundle:Article' )
                            ->find( $id )  ;
            $publishDate = $article->getPublishedAt();
        } else {
            $article = new Article();
            $publishDate = new \Datetime('today');
        }

        $author = $article->getAuthor();
        $tags = [];
        foreach( $article->getTags() as $tag ){
            $tags[] = $tag->getName();
        }
        $tagsString = implode( ', ', $tags );

        $form = $this->createFormBuilder( $article )
                     ->setAction( $this->generateUrl('article.store', ['id' => $id]) )
                     ->setMethod( 'POST' )
                     ->add('name', 'text', [ 'label' => 'Название статьи' ])
                     ->add('text', 'textarea', [ 'label' => 'Содержание статьи' ])
                     ->add( 'published_at', 'date', [
                         'label' => 'Дата публикации',
                         'data' => $publishDate
                     ])
                     ->add( 'type', ChoiceType::class, [
                         'label' => 'Тип статьи',
                         'choices' => self::ARTICLE_TYPES,
                         'attr' => [
                             'onChange' => 'javascript: trigger_fields(this)',
                         ],
                     ])
                     ->add('tags', 'text', [
                         'label' => 'Тэги',
                         'attr' => [
                             'class' => 'simple_article_field',
                         ],
                         'required' => false,
                         'data' => $tagsString,
                     ])
                     ->add('author', 'text', [
                         'label' => 'Автор',
                         'attr' => [
                             'class' => 'author_article_field',
                         ],
                         'required' => false,
                         'mapped' => false,
                         'data' => $author->isEmpty() ? '': $author[0]->getName()
                     ])
                     ->add('author_site', 'text', [
                         'label' => 'Сайт',
                         'attr' => [
                             'class' => 'author_article_field',
                         ],
                         'mapped' => false,
                         'required' => false,
                         'data' => $author->isEmpty() ? '' : $author[0]->getSite()
                     ])
                     ->add('save', 'submit')
                     ;

        return $form->getForm();

    }

    private function _prepareTags( $tags )
    {
        $result = $allTags = $searchTags = [];

        if( !empty($tags) ){

            $tagRepository = $this->getDoctrine()
                                  ->getRepository('ArticleBundle:Tag' );

            $em = $this->getDoctrine()->getManager();

            foreach( $tagRepository->findAll() as $tag ){
                $allTags[ $tag->getId() ] = $tag->getName();
            }

            foreach( explode(',', $tags) as $tag ){
                $tagName = trim( $tag );
                $searchTags[] = $tagName;

                if( !in_array($tagName, $allTags) ){
                    $tagEntity = new Tag();
                    $tagEntity->setName( $tag );
                    $em->persist( $tagEntity );
                    $result[] = $tagEntity;
                }

            }

            $result = array_merge( $result, $tagRepository->findBy(['name'=> $searchTags ]) );

        }

        return $result;
    }

    private function _prepareAuthor( $name, $site, $article )
    {

        $author = is_null( $article->getId() ) || !is_a( $article->getAuthor(), Author::class ) ? new Author() : $article->getAuthor();
        $author->setName( $name );
        $author->setSite( $site );
        $author->setArticle( $article );

        $em = $this->getDoctrine()->getManager();
        $em->persist( $author );

        return [
            $author
        ];
    }

}
