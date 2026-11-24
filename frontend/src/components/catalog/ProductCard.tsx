import React from 'react';
import { Link } from 'react-router-dom';
import { Store, ShoppingBag } from 'lucide-react';
import { Product } from '../../types';
import { formatRupiah } from '../../utils/formatters';

interface ProductCardProps {
  product: Product;
}

export const ProductCard: React.FC<ProductCardProps> = ({ product }) => {
  const p = product as any;
  const primaryImage = p.primary_image || p.images?.find((img: any) => img.is_primary)?.image_url || p.images?.[0]?.file_path || p.images?.[0]?.image_url;
  const displayPrice = product.min_price || product.base_price || p.min_price_amount || 0;
  const storeName = product.store?.name || p.store_name || 'BeliIn Merchant';
  const isOutOfStock = product.total_stock !== undefined && product.total_stock <= 0;

  return (
    <div className="group bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between">
      <Link to={`/products/${product.slug || product.id}`} className="block relative aspect-square overflow-hidden bg-slate-100">
        {primaryImage ? (
          <img
            src={primaryImage}
            alt={product.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
          />
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-gradient-to-br from-slate-100 to-slate-200">
            <ShoppingBag className="w-12 h-12 stroke-[1.5] text-slate-300" />
            <span className="text-xs mt-2 font-medium">BeliIn Product</span>
          </div>
        )}

        {isOutOfStock && (
          <div className="absolute inset-0 bg-slate-900/60 flex items-center justify-center backdrop-blur-xs">
            <span className="bg-rose-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
              Habis
            </span>
          </div>
        )}
      </Link>

      <div className="p-4 flex-1 flex flex-col justify-between">
        <div>
          {/* Store Info */}
          <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-1.5">
            <Store className="w-3.5 h-3.5 text-blue-600" />
            <span className="font-medium truncate max-w-[140px]">{storeName}</span>
          </div>

          {/* Product Title */}
          <Link to={`/products/${product.slug || product.id}`} className="block">
            <h3 className="text-sm font-semibold text-slate-800 line-clamp-2 hover:text-blue-600 transition-colors">
              {product.name}
            </h3>
          </Link>
        </div>

        {/* Pricing & Stock */}
        <div className="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between">
          <div>
            <p className="text-base font-extrabold text-blue-700">
              {formatRupiah(displayPrice)}
            </p>
            {product.total_stock !== undefined && (
              <p className="text-[11px] text-slate-400 mt-0.5">
                {product.total_stock > 0 ? `Stok: ${product.total_stock}` : 'Stok Kosong'}
              </p>
            )}
          </div>

          <Link
            to={`/products/${product.slug || product.id}`}
            className="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-xl text-xs font-semibold transition-colors flex items-center gap-1"
          >
            Pilih
          </Link>
        </div>
      </div>
    </div>
  );
};
