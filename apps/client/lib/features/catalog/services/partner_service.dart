import '../../../core/network/api_client.dart';

class SupplierModel {
  final String id;
  final String code;
  final String companyName;
  final String? contactName;
  final String? email;
  final String? phone;
  final String currency;
  final int paymentTermsDays;
  final bool isActive;

  SupplierModel({
    required this.id,
    required this.code,
    required this.companyName,
    this.contactName,
    this.email,
    this.phone,
    required this.currency,
    required this.paymentTermsDays,
    required this.isActive,
  });

  factory SupplierModel.fromJson(Map<String, dynamic> json) {
    return SupplierModel(
      id: json['id'] ?? '',
      code: json['code'] ?? '',
      companyName: json['company_name'] ?? '',
      contactName: json['contact_name'],
      email: json['email'],
      phone: json['phone'],
      currency: json['currency'] ?? 'MGA',
      paymentTermsDays: json['payment_terms_days'] ?? 0,
      isActive: json['is_active'] ?? true,
    );
  }
}

class CustomerModel {
  final String id;
  final String code;
  final String? companyName;
  final String? firstName;
  final String? lastName;
  final String? email;
  final String? phone;
  final String currency;
  final double creditLimit;
  final bool isActive;

  CustomerModel({
    required this.id,
    required this.code,
    this.companyName,
    this.firstName,
    this.lastName,
    this.email,
    this.phone,
    required this.currency,
    required this.creditLimit,
    required this.isActive,
  });

  factory CustomerModel.fromJson(Map<String, dynamic> json) {
    return CustomerModel(
      id: json['id'] ?? '',
      code: json['code'] ?? '',
      companyName: json['company_name'],
      firstName: json['first_name'],
      lastName: json['last_name'],
      email: json['email'],
      phone: json['phone'],
      currency: json['currency'] ?? 'MGA',
      creditLimit: json['credit_limit'] != null ? double.tryParse(json['credit_limit'].toString()) ?? 0.0 : 0.0,
      isActive: json['is_active'] ?? true,
    );
  }

  String get displayName {
    if (companyName != null && companyName!.isNotEmpty) {
      return companyName!;
    }
    return '${firstName ?? ''} ${lastName ?? ''}'.trim();
  }
}

class PartnerService {
  static final PartnerService instance = PartnerService._internal();

  PartnerService._internal();

  Future<List<SupplierModel>> getSuppliers() async {
    final response = await ApiClient.instance.get('/suppliers');
    final List<SupplierModel> suppliers = [];
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final list = response['data'];
      if (list is List) {
        for (final item in list) {
          if (item is Map<String, dynamic>) {
            suppliers.add(SupplierModel.fromJson(item));
          }
        }
      }
    }
    return suppliers;
  }

  Future<SupplierModel> createSupplier(Map<String, dynamic> data) async {
    final response = await ApiClient.instance.post('/suppliers', body: data);
    return SupplierModel.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<List<CustomerModel>> getCustomers() async {
    final response = await ApiClient.instance.get('/customers');
    final List<CustomerModel> customers = [];
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final list = response['data'];
      if (list is List) {
        for (final item in list) {
          if (item is Map<String, dynamic>) {
            customers.add(CustomerModel.fromJson(item));
          }
        }
      }
    }
    return customers;
  }

  Future<CustomerModel> createCustomer(Map<String, dynamic> data) async {
    final response = await ApiClient.instance.post('/customers', body: data);
    return CustomerModel.fromJson(response['data'] as Map<String, dynamic>);
  }
}
