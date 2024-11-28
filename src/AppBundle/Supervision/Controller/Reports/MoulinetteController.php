<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 21/05/2019
 * Time: 09:16
 */

namespace AppBundle\Supervision\Controller\Reports;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Form\Reports\MoulinetteType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Security\RightAnnotation;

/**
 * Class MoulinetteController
 *
 * @package         AppBundle\Controller\Reports
 * @Route("report")
 */
class MoulinetteController extends Controller
{

    /**
     *
     * @param Request $request
     *
     * @return Response
     * @Route("/supervision_moulinette",name="supervision_moulinette" ,options={"expose"=true})
     */
    public function generateMoulinette(Request $request){

        $form = $this->createForm(MoulinetteType::class, null);
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $startDate = $form->get('startDate')->getData()->format('Y-m-d');
                $endDate = $form->get('endDate')->getData()->format('Y-m-d');
                $criteria['restaurants'] = $form->getData()['restaurants'];
                $type=$form->getData()['type'];
               $list=array();
                if (sizeof($criteria['restaurants']) == 0) {
                    $list = array('2418', '2710', '2723', '2735', '2750', '1764', '2771', '2772', '2773', '2777', '1747', '1441', '1015', '1291', '1292', '6293', '1294', '6295', '6296', '6297', '6298', '1299');
                }else{
                    foreach ($criteria['restaurants'] as $restaurant) {
                        $list[] = (string)$restaurant->getCode();
                    }
                }
                $codes="";
                foreach ($list as $l){
                    $codes.=$l."-";
                }
                $progress = new ImportProgression();
                $progress->setStartDateTime(new \DateTime())
                    ->setNature('coeff')
                    ->setStatus('pending');
                $this->getDoctrine()->getManager()->persist($progress);
                $this->getDoctrine()->getManager()->flush();
                $this->get('toolbox.command.launcher')->execute(
                    "saas:moulinette:calcul ".$startDate." ".$endDate." ".$type." ".$progress->getId()." ".$codes
                );
                return $this->render(
                    "@Supervision/Reports/Moulinette/index_moulinette.html.twig",
                    [
                        'form' => $form->createView(),
                        'progressID' => $progress->getId(),
                        'type' => $type
                    ]
                );

            }
        }
        return $this->render(
            "@Supervision/Reports/Moulinette/index_moulinette.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }


    /**
     *
     * @param $documents
     *
     * @return Response
     * @Route("/zip_moulinette/{progressId}",name="zip_moulinette")
     */
    public function zipDownloadAllAction($progressId)
    {
        $files = array();
        $zip = new \ZipArchive();
        $zipName= $this->getParameter('kernel.root_dir')
            ."/../data/export/Moulinette.zip";
        $progress = $this->getDoctrine()->getRepository(ImportProgression::class)
            ->findOneBy(array('id' =>$progressId));
        $pathName= $this->getParameter('kernel.root_dir')
        . "/../data/export/".$progress->getId()."/";
        $documents=scandir($pathName);

        foreach ($documents as $d) {
            if (!in_array($d, array(".", ".."))) {
                array_push($files, $pathName . $d);
            }
     }

        $zip->open($zipName,  \ZipArchive::CREATE);
        foreach ($files as $f) {
            if (!in_array($f, array(".", ".."))){
                $zip->addFromString(basename($f), file_get_contents($f));
        }
        }

        $zip->close();
        $zipname='Moulinette_'.date('dmY_His').".zip";
        $response = new Response(file_get_contents($zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipname . '"');
        $response->headers->set('Content-length', filesize($zipName));
        unlink($zipName);
//        unlink($pathName);
        return $response;
    }
}