<?php

namespace App\Controller\dw;

use App\Controller\Controller;
use App\Entity\dw\DocInternesearch;
use App\Form\dw\DocInterneSearchType;
use App\Entity\dw\DwProcessusProcedure;
use App\Repository\dw\DwProcessusProcedureRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/documentation")
 */
class DocumentationProcessusController extends Controller
{
    /**
     * @Route("/documentation-interne", name="documentation_interne")
     */
    public function documentationInterne(Request $request)
    {
        $docInterneSearch = new DocInternesearch;

        $form = $this->getFormFactory()->createBuilder(DocInterneSearchType::class, $docInterneSearch, [
            'method' => 'GET'
        ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $docInterneSearch = $form->getData();
        }

        $criteria = [];
        $criteria = $docInterneSearch->toArray();
        $page = $request->query->getInt('page', 1);
        $limit = 30;

        /** @var DwProcessusProcedureRepository $repository */
        $repository = $this->getEntityManager()->getRepository(DwProcessusProcedure::class);
        $paginationData = $repository->findPaginatedAndFiltered($page, $limit, $docInterneSearch);

        return $this->render('dw/documentationInterne.html.twig', [
            'form'        => $form->createView(),
            'data'        => $paginationData['data'],
            'currentPage' => $paginationData['currentPage'],
            'totalPages'  => $paginationData['lastPage'],
            'resultat'    => $paginationData['totalItems'],
            'criteria'    => $criteria,
        ]);
    }
}
