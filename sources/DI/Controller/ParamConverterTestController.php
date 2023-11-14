<?php

namespace Combodo\iTop\DI\Controller;

use Combodo\iTop\DI\Dto\TestDto;
use Person;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Combodo\iTop\DI\Services\Orm;

/**
 * Param converter test controller.
 *
 * public const ENUM_SANITIZATION_FILTER_INTEGER = 'integer';
 * public const ENUM_SANITIZATION_FILTER_CLASS = 'class';
 * public const ENUM_SANITIZATION_FILTER_STRING = 'string';
 * public const ENUM_SANITIZATION_FILTER_CONTEXT_PARAM = 'context_param';
 * public const ENUM_SANITIZATION_FILTER_ROUTE = 'route';
 * public const ENUM_SANITIZATION_FILTER_OPERATION = 'operation';
 * public const ENUM_SANITIZATION_FILTER_PARAMETER = 'parameter';
 * public const ENUM_SANITIZATION_FILTER_FIELD_NAME = 'field_name';
 * public const ENUM_SANITIZATION_FILTER_TRANSACTION_ID = 'transaction_id';
 * public const ENUM_SANITIZATION_FILTER_ELEMENT_IDENTIFIER = 'element_identifier';
 * public const ENUM_SANITIZATION_FILTER_VARIABLE_NAME = 'variable_name';
 * public const ENUM_SANITIZATION_FILTER_RAW_DATA = 'raw_data';
 * public const ENUM_SANITIZATION_FILTER_URL = 'url';
 */
class ParamConverterTestController extends AbstractController
{

	#[Route('/param_converter_test/Person/{person_id<\d+>}', name: 'param_converter_test', methods: ['GET'], priority: 1)]
	public function convert(
		Request $request,
		#[Orm(mapping: 'person_id')] Person $person,
		#[MapQueryParameter(filter: \FILTER_VALIDATE_INT)] int $age = 22
	) : Response
	{
		$response = new JsonResponse([
			'sanitization' => [
				'name' => $person->GetName(),
				'age' => $age,
			]
		]);
		$response->setEncodingOptions( $response->getEncodingOptions() | JSON_PRETTY_PRINT );
		return $response;
	}


	#[Route('/param_converter_test/dto', name: 'param_converter_dto', methods: ['GET'])]
	public function validation(
		#[MapQueryString] TestDto $query
	) : Response
	{


		$response = new JsonResponse([
		]);
		$response->setEncodingOptions( $response->getEncodingOptions() | JSON_PRETTY_PRINT );
		return $response;
	}

}
