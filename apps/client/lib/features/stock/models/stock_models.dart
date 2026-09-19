class WarehouseModel {
  final String id;
  final String code;
  final String name;

  const WarehouseModel({
    required this.id,
    required this.code,
    required this.name,
  });

  factory WarehouseModel.fromJson(Map<String, dynamic> json) {
    return WarehouseModel(
      id: json['id']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
    );
  }
}

class StockBalanceModel {
  final String productId;
  final String warehouseId;
  final double onHand;
  final double reserved;
  final double damaged;
  final double available;

  const StockBalanceModel({
    required this.productId,
    required this.warehouseId,
    required this.onHand,
    required this.reserved,
    required this.damaged,
    required this.available,
  });

  factory StockBalanceModel.fromJson(Map<String, dynamic> json) {
    double number(String key) => double.tryParse('${json[key] ?? 0}') ?? 0;

    return StockBalanceModel(
      productId: json['product_id']?.toString() ?? '',
      warehouseId: json['warehouse_id']?.toString() ?? '',
      onHand: number('on_hand'),
      reserved: number('reserved'),
      damaged: number('damaged'),
      available: number('available'),
    );
  }
}

class StockMovementModel {
  final String id;
  final String type;
  final String productName;
  final String? sourceWarehouseName;
  final String? destinationWarehouseName;
  final double quantity;
  final DateTime? createdAt;
  final String? reason;

  const StockMovementModel({
    required this.id,
    required this.type,
    required this.productName,
    this.sourceWarehouseName,
    this.destinationWarehouseName,
    required this.quantity,
    this.createdAt,
    this.reason,
  });

  factory StockMovementModel.fromJson(Map<String, dynamic> json) {
    double quantity = double.tryParse('${json['quantity'] ?? 0}') ?? 0;
    final product = json['product'] is Map
        ? Map<String, dynamic>.from(json['product'])
        : const <String, dynamic>{};
    final source = json['source_warehouse'] is Map
        ? Map<String, dynamic>.from(json['source_warehouse'])
        : null;
    final destination = json['destination_warehouse'] is Map
        ? Map<String, dynamic>.from(json['destination_warehouse'])
        : null;

    return StockMovementModel(
      id: json['id']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      productName:
          product['name']?.toString() ??
          product['sku']?.toString() ??
          'Produit',
      sourceWarehouseName: source?['name']?.toString(),
      destinationWarehouseName: destination?['name']?.toString(),
      quantity: quantity,
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? ''),
      reason: json['reason']?.toString(),
    );
  }

  String get typeLabel {
    switch (type) {
      case 'receipt':
        return 'Réception';
      case 'issue':
        return 'Sortie';
      case 'transfer':
        return 'Transfert';
      case 'reverse':
        return 'Contre-opération';
      default:
        return type;
    }
  }
}
