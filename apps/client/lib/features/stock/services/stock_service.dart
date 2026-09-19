import '../../../core/network/api_client.dart';
import '../models/stock_models.dart';

class StockService {
  static final StockService instance = StockService._internal();

  StockService._internal();

  Future<List<WarehouseModel>> getWarehouses() async {
    final response = await ApiClient.instance.get('/warehouses');
    return _list(response).map(WarehouseModel.fromJson).toList();
  }

  Future<List<StockBalanceModel>> getBalances({int limit = 100}) async {
    final response = await ApiClient.instance.get(
      '/stock-balances',
      queryParams: {'limit': '$limit'},
    );
    return _list(response).map(StockBalanceModel.fromJson).toList();
  }

  Future<List<StockMovementModel>> getMovements({int limit = 50}) async {
    final response = await ApiClient.instance.get(
      '/stock-movements',
      queryParams: {'limit': '$limit'},
    );
    return _list(response).map(StockMovementModel.fromJson).toList();
  }

  Future<void> receive({
    required String productId,
    required String warehouseId,
    required double quantity,
  }) async {
    final response = await ApiClient.instance.post(
      '/stock-movements',
      body: {
        'type': 'receipt',
        'product_id': productId,
        'destination_warehouse_id': warehouseId,
        'quantity': quantity,
        'idempotency_key':
            'flutter-receipt-${DateTime.now().microsecondsSinceEpoch}',
      },
    );

    if (response is! Map<String, dynamic> || response['data'] == null) {
      throw ApiException(
        statusCode: 500,
        message: 'Échec de réception du stock',
      );
    }
  }

  List<Map<String, dynamic>> _list(dynamic response) {
    if (response is Map<String, dynamic> && response['data'] is List) {
      return (response['data'] as List)
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    }
    return const [];
  }
}
