class UnitModel {
  final String id;
  final String code;
  final String name;
  final int precision;
  final bool isBase;

  UnitModel({
    required this.id,
    required this.code,
    required this.name,
    required this.precision,
    required this.isBase,
  });

  factory UnitModel.fromJson(Map<String, dynamic> json) {
    return UnitModel(
      id: json['id'] ?? '',
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      precision: json['precision'] ?? 0,
      isBase: json['is_base'] ?? true,
    );
  }
}

class CategoryModel {
  final String id;
  final String code;
  final String name;
  final String? description;
  final List<CategoryModel> children;

  CategoryModel({
    required this.id,
    required this.code,
    required this.name,
    this.description,
    this.children = const [],
  });

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    var rawChildren = json['children'];
    List<CategoryModel> childrenList = [];
    if (rawChildren is List) {
      childrenList = rawChildren.map((c) => CategoryModel.fromJson(c as Map<String, dynamic>)).toList();
    }

    return CategoryModel(
      id: json['id'] ?? '',
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      description: json['description'],
      children: childrenList,
    );
  }
}

class ProductVariantModel {
  final String id;
  final String sku;
  final String? barcode;
  final String? qrCode;
  final String? name;
  final Map<String, dynamic>? attributeValues;
  final bool isActive;

  ProductVariantModel({
    required this.id,
    required this.sku,
    this.barcode,
    this.qrCode,
    this.name,
    this.attributeValues,
    this.isActive = true,
  });

  factory ProductVariantModel.fromJson(Map<String, dynamic> json) {
    return ProductVariantModel(
      id: json['id'] ?? '',
      sku: json['sku'] ?? '',
      barcode: json['barcode'],
      qrCode: json['qr_code'],
      name: json['name'],
      attributeValues: json['attribute_values'] is Map ? Map<String, dynamic>.from(json['attribute_values']) : null,
      isActive: json['is_active'] ?? true,
    );
  }
}

class ProductModel {
  final String id;
  final String sku;
  final String? reference;
  final String name;
  final String? description;
  final String state; // new, used, refurbished, damaged
  final bool hasVariants;
  final bool requiresSerialNumber;
  final double alertThreshold;
  final bool isActive;
  final UnitModel? baseUnit;
  final CategoryModel? category;
  final List<ProductVariantModel> variants;

  ProductModel({
    required this.id,
    required this.sku,
    this.reference,
    required this.name,
    this.description,
    required this.state,
    required this.hasVariants,
    required this.requiresSerialNumber,
    required this.alertThreshold,
    required this.isActive,
    this.baseUnit,
    this.category,
    this.variants = const [],
  });

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    var rawVariants = json['variants'];
    List<ProductVariantModel> variantList = [];
    if (rawVariants is List) {
      variantList = rawVariants.map((v) => ProductVariantModel.fromJson(v as Map<String, dynamic>)).toList();
    }

    return ProductModel(
      id: json['id'] ?? '',
      sku: json['sku'] ?? '',
      reference: json['reference'],
      name: json['name'] ?? '',
      description: json['description'],
      state: json['state'] ?? 'new',
      hasVariants: json['has_variants'] ?? false,
      requiresSerialNumber: json['requires_serial_number'] ?? false,
      alertThreshold: (json['alert_threshold'] != null) ? double.tryParse(json['alert_threshold'].toString()) ?? 0.0 : 0.0,
      isActive: json['is_active'] ?? true,
      baseUnit: json['base_unit'] is Map ? UnitModel.fromJson(json['base_unit']) : null,
      category: json['category'] is Map ? CategoryModel.fromJson(json['category']) : null,
      variants: variantList,
    );
  }

  String get stateLabel {
    switch (state) {
      case 'new':
        return 'Neuf';
      case 'used':
        return 'Occasion';
      case 'refurbished':
        return 'Reconditionné';
      case 'damaged':
        return 'Endommagé';
      default:
        return state;
    }
  }
}
