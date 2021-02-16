<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller extends AbstractController
{

    function index()
    {
        return new JsonResponse('ReallyDirty API v1.0');
    }

    function doctor(Request $request)
    {
        if ($request->getMethod() === 'GET') {
            // Get doctor
            $id = $request->get('id');

            /** @var EntityManagerInterface $doctrineManager */
            $doctrineManager = $this->getDoctrine()->getManager();

            // Get doctor
            $doctorEntity = $doctrineManager->createQueryBuilder()
                ->select('doctor')
                ->from(DoctorEntity::class, 'doctor')
                ->where('doctor.id=:id')
                ->setParameter('id', $id)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($doctorEntity) {
                return new JsonResponse(
                    [
                        'id' => $doctorEntity->getId(),
                        'firstName' => $doctorEntity->getFirstName(),
                        'lastName' => $doctorEntity->getLastName(),
                        'specialization' => $doctorEntity->getSpecialization(),
                    ]
                );
            } else {
                return new JsonResponse([], 404);
            }
        } elseif ($request->getMethod() === 'POST') {
            // Add doctor
            $doctrineManager = $this->getDoctrine()->getManager();

            $doctorEntity = new DoctorEntity();
            $doctorEntity->setFirstName($request->get('firstName'));
            $doctorEntity->setLastName($request->get('lastName'));
            $doctorEntity->setSpecialization($request->get('specialization'));

            $doctrineManager->persist($doctorEntity);
            $doctrineManager->flush();

            // Result
            return new JsonResponse(['id' => $doctorEntity->getId()]);
        }

        return new JsonResponse([], 400);
    }

    function slots(int $doctorId, Request $request)
    {
        /** @var EntityManagerInterface $doctrineManager */
        $doctrineManager = $this->getDoctrine()->getManager();

        // Get doctor
        $queryBuilder = $doctrineManager->createQueryBuilder()
            ->select('doctor')
            ->from(DoctorEntity::class, 'doctor')
            ->where('doctor.id=:id')
            ->setParameter('id', $doctorId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($queryBuilder) {
            if ($request->getMethod() === 'GET') {
                //Get slots
                /** @var SlotEntity[] $slots */
                $slots = $queryBuilder->slots();

                if (count($slots)) {
                    $responseData = [];
                    foreach ($slots as $slot) {
                        $responseData[] = [
                            'id' => $slot->getId(),
                            'day' => $slot->getDay()->format('Y-m-d'),
                            'from_hour' => $slot->getFromHour(),
                            'duration' => $slot->getDuration()
                        ];
                    }
                    return new JsonResponse($responseData);
                } else {
                    return new JsonResponse([]);
                }
            } elseif ($request->getMethod() === 'POST') {
                // Add slot
                $slot = new SlotEntity();
                $slot->setDay(new DateTime($request->get('day')));
                $slot->setDoctor($queryBuilder);
                $slot->setDuration((int)$request->get('duration'));
                $slot->setFromHour($request->get('from_hour'));

                $doctrineManager->persist($slot);
                $doctrineManager->flush();

                //Result
                return new JsonResponse(['id' => $slot->getId()]);
            }
        } else {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([], 400);
    }

}
