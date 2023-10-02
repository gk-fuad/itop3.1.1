<?php

namespace Combodo\iTop\DI\Controller;

use Combodo\iTop\DI\Form\Manager\ObjectFormManager;
use Combodo\iTop\DI\Form\Type\Compound\PartialObjectType;
use Combodo\iTop\DI\Form\Type\Compound\ObjectType;
use Combodo\iTop\DI\Services\ObjectService;
use Exception;
use MetaModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * File test controller.
 *
 */
class FileTestController extends AbstractController
{


	/**
	 *
	 * @Route("/file_test/form", name="file_test_form", methods={"POST"})
	 */
	public function showForm(Request $request) : Response
	{
		$oTask = [
			'task' => 'Write a blog post'
		];

		$oForm = $this->createFormBuilder($oTask)
			->add('task', TextType::class)
			->add('doc', FileType::class)
			->add('docs', FileType::class, [
				'multiple' => true
			])
			->add('save', SubmitType::class, ['label' => 'Create Task'])
			->getForm();

		$oForm->handleRequest($request);

		// submitted and valid
		if ($oForm->isSubmitted() && $oForm->isValid()) {

			$sDataPath = APPROOT . 'data';

			$file = $oForm['doc']->getData();
			$file->move($sDataPath, $file->getClientOriginalName());

			$files = $oForm['docs']->getData();
			foreach ($files as $file){
				$file->move($sDataPath, $file->getClientOriginalName());
			}

			// return object form
			return new JsonResponse([
				'succeeded' => true
			]);
		}

		// return object form
		return new JsonResponse([
			'template' => $this->renderView('DI/form/form.html.twig', [
				'form' => $oForm->createView(),
			])
		]);
	}



}
