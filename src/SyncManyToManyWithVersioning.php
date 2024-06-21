<?php

namespace Kiqstyle\EloquentVersionable;

use Illuminate\Database\Eloquent\Model;

class SyncManyToManyWithVersioning
{
    private Model $entity;
    private Model $manyToManyRelation;
    private array $fields = [
        'entityKey' => null,
        'relationKey' => null
    ];

    public function run(Model $entity, array $newRelationsIds, Model $manyToManyRelation, array $fields)
    {
        $this->entity = $entity;
        $this->manyToManyRelation = $manyToManyRelation;
        $this->fields = $fields;

        $oldRelationsIds = $manyToManyRelation->where($this->fields['entityKey'], $this->entity->id)
            ->pluck($this->fields['relationKey'])
            ->toArray();

        $relationsToExclude = array_diff($oldRelationsIds, $newRelationsIds);
        $relationsToInclude = array_diff($newRelationsIds, $oldRelationsIds);

        $this->removeRelations($relationsToExclude);
        $this->createRelations($relationsToInclude);
    }

    private function removeRelations(array $relationsToExclude): void
    {
        foreach ($relationsToExclude as $relationId) {
            $relationToExclude = $this->manyToManyRelation->where($this->fields['relationKey'], $relationId)
                ->where($this->fields['entityKey'], $this->entity->id)
                ->first();

            $relationToExclude->delete();
        }
    }

    private function createRelations(array $relationsToInclude): void
    {
        foreach ($relationsToInclude as $relationId) {
            $data = [
                $this->fields['entityKey'] => $this->entity->id,
                $this->fields['relationKey'] => $relationId
            ];

            $this->manyToManyRelation->create($data);
        }
    }
}
