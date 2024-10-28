<?php

namespace App\Repositories;

use App\Models\Shoe;
use App\Repositories\Contracts\ShoeRepositoryInterface;

class ShoeRepository implements ShoeRepositoryInterface
{
    public function getPopularShoes($limit = 5)
    {
        return Shoe::where('is_popular', true)->limit($limit)->get();
    }

    public function searchByName(string $keyword)
    {
        return  Shoe::where('name', 'LIKE', '%' . $keyword . '%')->get();
    }

    public function getAllNewShoes()
    {
        return Shoe::latest()->get();
    }

    public function find($id)
    {
        return Shoe::find($id);
    }

    public function getPrice($tiketId)
    {
        $shoe = $this->find($tiketId);
        return $shoe ? $shoe->price : 0;
    }
}
