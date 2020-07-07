<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LanguagesTableSeeder::class);
        $this->call(RecordTypesTableSeeder::class);
        $this->call(ProductCategoriesTableSeeder::class);
        $this->call(BusinessTypesTableSeeder::class);
        $this->call(BunqIdealIssuersTableSeeder::class);
        $this->call(RoleTranslationTableSeeder::class);
    }
}
