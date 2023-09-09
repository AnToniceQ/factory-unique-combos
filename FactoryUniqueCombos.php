<?php

namespace App\Helpers;

use App\Exceptions\FactoryMaxTriesExceededException;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 *
 * A class that is responsible for ensuring unique values for columns within a Laravel Factory.
 *
 */
trait FactoryUniqueCombos
{
    // The combos in the database and the ones the factory creates in progress.
    private ?Collection $combos = null;

    /**
     *
     * @param array $uniqueColumns The dictionary with columns which we want to be unique in our database, with the key as a column name and a value as an anonymous function, that will be called within the method to that particular column. Ex.: ['col_name', function() { $faker->foo() }]
     * @param int $maxTries The maximum amount of tries the factory will try to generate new values to get a unique combination until an exception is thrown.
     *
     * @return array The dictionary of the unique values
     *
     * @throws FactoryMaxTriesExceededException The exception that is thrown when $maxTries have been exceeded.
     */
    protected function uniqueCombos(array $uniqueColumns, int $maxTries = 5): array
    {
        // Runs only once - gets the already existing combos from the database table of the model.
        if ($this->combos == null) {
            $modelTable = app($this->modelName())->getTable();
            $builder = DB::table($modelTable)->select(array_keys($uniqueColumns));
            $this->combos = $builder->get();
        }

        // Start trying to generate unique values.
        $tryCount = 0;
        do {
            // Create a new STD class to assign column values and for future comparison with $combos.
            $wantedUniqueValues = new stdClass();

            // For every wanted unique column, assign to the STD class a value.
            foreach ($uniqueColumns as $column => $function) {
                // Get the value from the anonymous function.
                $functionValue = $function();

                // When the value is a Model, get only the PK! (For comparison purposes)
                if($functionValue instanceof Model)
                    $functionValue = $functionValue->getKey();

                // Assign to the STD class a column property and its value.
                $wantedUniqueValues->$column = $functionValue;
            }

            // When a combination is not present in $combos, push it to $combos and return the STD class as dictionary!
            if (!$this->combos->contains($wantedUniqueValues)) {
                $this->combos->push($wantedUniqueValues);
                return json_decode(json_encode($wantedUniqueValues), true);
            }

            // Otherwise increment the $tryCount and try again.
            $tryCount++;
        } while ($tryCount < $maxTries);

        // When the $tryCount is higher than $maxTries, throw a exception.
        throw new FactoryMaxTriesExceededException(get_class($this), $maxTries, array_keys($uniqueColumns));
    }
}
