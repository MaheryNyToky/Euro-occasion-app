import '../../../core/network/api_client.dart';
import '../models/catalog_models.dart';

class CatalogService {
  static final CatalogService instance = CatalogService._internal();

  CatalogService._internal();

  Future<List<ProductModel>> getProducts({
    String? search,
    String? categoryId,
    String? state,
    bool? isActive,
    int page = 1,
    int limit = 25,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
      'limit': limit.toString(),
    };

    if (search != null && search.trim().isNotEmpty) {
      queryParams['search'] = search.trim();
    }
    if (categoryId != null && categoryId.isNotEmpty) {
      queryParams['category_id'] = categoryId;
    }
    if (state != null && state.isNotEmpty) {
      queryParams['state'] = state;
    }
    if (isActive != null) {
      queryParams['is_active'] = isActive.toString();
    }

    final response = await ApiClient.instance.get('/products', queryParams: queryParams);

    final List<ProductModel> products = [];
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final list = response['data'];
      if (list is List) {
        for (final item in list) {
          if (item is Map<String, dynamic>) {
            products.add(ProductModel.fromJson(item));
          }
        }
      }
    }

    return products;
  }

  Future<ProductModel> getProduct(String id) async {
    final response = await ApiClient.instance.get('/products/$id');
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      return ProductModel.fromJson(response['data'] as Map<String, dynamic>);
    }
    throw ApiException(statusCode: 404, message: 'Produit non trouvé');
  }

  Future<List<CategoryModel>> getCategories() async {
    final response = await ApiClient.instance.get('/categories');
    final List<CategoryModel> categories = [];
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final list = response['data'];
      if (list is List) {
        for (final item in list) {
          if (item is Map<String, dynamic>) {
            categories.add(CategoryModel.fromJson(item));
          }
        }
      }
    }
    return categories;
  }

  Future<List<UnitModel>> getUnits() async {
    final response = await ApiClient.instance.get('/units');
    final List<UnitModel> units = [];
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final list = response['data'];
      if (list is List) {
        for (final item in list) {
          if (item is Map<String, dynamic>) {
            units.add(UnitModel.fromJson(item));
          }
        }
      }
    }
    return units;
  }

  Future<ProductModel> createProduct(Map<String, dynamic> productData) async {
    final response = await ApiClient.instance.post('/products', body: productData);
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      return ProductModel.fromJson(response['data'] as Map<String, dynamic>);
    }
    throw ApiException(statusCode: 500, message: 'Échec de création du produit');
  }

  Future<void> archiveProduct(String id, {String? reason}) async {
    await ApiClient.instance.post('/products/$id/archive', body: {
      'reason': reason ?? 'Archivage depuis application client',
    });
  }
}
