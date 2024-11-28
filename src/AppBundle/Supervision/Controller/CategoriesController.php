<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 08/03/2016
 * Time: 10:30
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Merchandise\Entity\ProductCategories;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Supervision\Form\CategoryType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class CategoriesController extends Controller
{

    /**
     * @RightAnnotation ("categories_list")
     * @param Request           $request
     * @param ProductCategories $category
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @Route("/categories_list/{category}",name="categories_list",options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function categoriesListAction(Request $request, ProductCategories $category = null)
    {
        if ($category == null) {
            $category = new ProductCategories();
        } elseif ($category->getNameTranslation('nl') == null) {
            $category->addNameTranslation('nl', '');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $categories = $this->getDoctrine()->getRepository(ProductCategories::class)->findByActive(true);
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $type = ($category->getId() != null) ? 'edit' : 'plus';
            if ($request->getMethod() === "POST") {
                try {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $this->get('category.service')->saveCategory($category);
                        $newCategory = new ProductCategories();
                        $form = $this->createForm(CategoryType::Class, $newCategory);
                        $response->setData(
                            [
                                "data" => [
                                    $this->renderView(
                                        '@Supervision/modals/details_list_category.html.twig',
                                        array(
                                            'category' => $category,
                                        )
                                    ),
                                    [
                                        "id" => $category->getId(),
                                        "order" => $category->getOrder() ? $category->getOrder() : '',
                                        "name" => $category->getName(),
                                        "groupCategory" => $category->getCategoryGroup()->getName(),
                                        "taxBe" => (!is_null($category->getTaxBe())) ? $category->getTaxBe()."%" : '',
                                        "taxLux" => (!is_null($category->getTaxLux())) ? $category->getTaxLux(
                                        )."%" : '',
                                        "eligible" => ($category->getEligible())
                                            ? $this->get('translator')->trans('keyword.yes')
                                            : $this->get('translator')->trans('keyword.no'),
                                        "btn" => $this->renderView(
                                            '@Supervision/parts/btn_action_template.html.twig',
                                            array(
                                                'id' => $category->getId(),
                                            )
                                        ),
                                    ],
                                    $this->renderView(
                                        '@Supervision/parts/form_add_edit_category.html.twig',
                                        array(
                                            'form' => $form->createView(),
                                            'type' => 'plus',
                                        )
                                    ),
                                ],
                            ]
                        );
                    } else {
                        $form->addError(new FormError($this->get('translator')->trans('form.error')));

                        $response->setData(
                            [
                                "formError" => [
                                    $this->renderView(
                                        '@Supervision/parts/form_add_edit_category.html.twig',
                                        array(
                                            'form' => $form->createView(),
                                            'type' => $type,
                                        )
                                    ),
                                ],
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    $response->setData(
                        [
                            "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                        ]
                    );
                    throw $e;
                }

                return $response;
            } else {
                $response->setData(
                    [
                        "data" => [
                            $this->renderView(
                                '@Supervision/parts/form_add_edit_category.html.twig',
                                array(
                                    'form' => $form->createView(),
                                    'type' => $type,
                                )
                            ),
                        ],
                    ]
                );

                return $response;
            }
        }

        return $this->render(
            "@Supervision/categories_list.html.twig",
            array(
                'form' => $form->createView(),
                'categories' => $categories,
                'type' => 'plus',
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/delete_category/{category}",name="delete_category", options={"expose"=true})
     */
    public function deleteCategoryAction(Request $request, ProductCategories $category)
    {
        $session = $this->get('session');
        $form = $this->createFormBuilder(
            null,
            array('action' => $this->generateUrl('delete_category', array('category' => $category->getId())))
        )->getForm();
        $text_button = $this->get('translator')->trans('category.list.delete', array(), 'supervision');
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $deleted = $this->get('category.service')->deleteCategory($category);
                if ($deleted) {
                    $session->getFlashBag()->set('success', 'category.list.delete_success');
                } else {
                    $session->getFlashBag()->set('error', 'category.list.delete_fails');
                }
            }

            return $this->redirectToRoute("categories_list");
        }

        return new JsonResponse(
            array(
                'data' => true,
                'html' => $this->renderView(
                    '@Supervision/parts/delete.html.twig',
                    array(
                        'form' => $form->createView(),
                        'text_button' => $text_button,
                    )
                ),
            )
        );
    }

    /**
     * @param ProductCategories $category
     * @return JsonResponse
     * @Route("/json/category_detail/{category}",name="category_detail",options={"expose"=true})
     */
    public function categoryDetailJsonAction(ProductCategories $category)
    {

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Supervision/modals/details_list_category.html.twig",
                    array(
                        'category' => $category,
                    )
                ),
            )
        );
    }
}
