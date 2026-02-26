<?php

namespace App\Controller;

use App\Entity\MediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class CreateMediaObjectAction extends AbstractController
{
    public function __invoke(Request $request): MediaObject
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $mediaObject = new MediaObject();
        $mediaObject->setFile($uploadedFile);
        
        // Les autres champs (category, candidate) seront gérés 
        // par le denormalizer si passés dans le body, ou manuellement ici si multipart pose souci.
        // Avec API Platform v3+, le multipart est bien géré si configuré dans l'opération.
        
        // Hack: API Platform deserializer might not populate "category" from multipart form-data easily depending on version.
        // Let's populate manually from request if needed, but let's trust API Platform first.
        // Actually, mixing file upload and JSON-LD IRI relations in multipart is tricky.
        // Standard way: Send 'file' as binary, and other fields as text.
        
        // Manual population for safety in this custom controller:
        if ($request->request->has('category')) {
             $categoryEnum = \App\Enum\MediaCategory::tryFrom($request->request->get('category'));
             if ($categoryEnum) {
                 $mediaObject->setCategory($categoryEnum);
             }
        }
        
        // For relation 'candidate', it's an IRI string in the request.
        // We'd need to resolve it. OR we let the standard deserializer handle it AFTER this controller?
        // No, custom controller replaces the "Read/Deserialize/Validate/Persist" chain usually? 
        // No, defaults: "defaults: ['_api_receive' => false]" is old style.
        // In V3, controller returns the object, then it goes to Validate -> Persist.
        // So we just return the object with the file set.
        
        return $mediaObject;
    }
}
