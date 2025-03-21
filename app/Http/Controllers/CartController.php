<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        // validasi yang harus di isi
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        // autentikasi user
        $user = Auth::user();

        // cek apakah produk sudah ada di keranjang
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id' ,$request->product_id)
            ->first();

        if($cartItem)
        {
            // jika sudah ada, update jumlahnya
            $cartItem->increment('quantity', $request->quantity);
        } else {
            // jika belum ada, buat baru
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' =>$request->product_id,
                'quantity'=>$request->quantity
            ]);
        }

        return response()->json($cartItem, 201);
    }

    public function viewCart()
    {
        // autentikasi user
        $user = Auth::user();

        // ambil relasi dari user
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();

        // hitung subtotal harga
        $totalPrice = 0;
        $cartData = $cartItems->map(function($cart) use($totalPrice){
            $subtotal = $cart->product->price * $cart->quantity;
            $totalPrice += $subtotal;
            return [
                'id' => $cart->id,
                'product_id' => $cart->product->id,
                'product_name' => $cart->product->name,
                'quantity' => $cart->quantity,
                'price' => $cart->product->price,
                'subtotal' => $subtotal
            ];
        });

        // kembalikan response json
        return response()->json([
            'items' => $cartData,
            'total_price' => $totalPrice
        ]);
    }

    public function updateCart(Request $request, $id)
    {
        // validasi dengan quantity
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        // autentikasi user
        $user = Auth::user();

        // relasi cart ke user_id
        $cartItem = Cart::where('id',$id)->where('user_id' ,$user->id)->first();

        // jika cart atau keranjang kosong
        if(!$cartItem)
        {
            return response()->json(['message' => 'item tidak ditemukan di keranjang!'], 404);
        }

        // update cart atau keranjang
        $cartItem->update([
            'quantity' => $request->quantity
        ]);

        return response()->json($cartItem);
    }

    public function removeFromCart($id)
    {
        // autentikasi user
        $user = Auth::user();

        // relasi ke user_id
        $cartItem = Cart::where('id', $id)->where('user_id', $user->id)->first();

        // jika user belum login
        if(!$cartItem)
        {
            return response()->json(['message' => 'item gagal di hapus']);
        }

        // hapus barang di keranjang
        $cartItem->delete();

        return response()->json(['message' => 'Item di hapus!']);
    }
}
