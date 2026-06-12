<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class ProductAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Product::class,
            'placeholder' => 'Choisir ou taper le nom du produit souhaité',
            'searchable_fields' => ['name','libelle'], //Bug introduit avec nouvelle version ux-autocomplete, cf
            'label' => 'Choisir un produit',
            'multiple' => false,
            'choice_label' => fn (Product $product) => $product->getName().' - '.$product->getLibelle(),
            'query_builder' => fn (ProductRepository $parametreRepository) => $parametreRepository->createQueryBuilder('p')
                ->andWhere('p.libelle is not null')
                ->andWhere('p.id > :min')
                ->andWhere('p.category = :valide')
                ->setParameter('valide', 'Validé')
                ->setParameter('min', 0)
                ->addOrderBy('p.id', 'DESC'),
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
