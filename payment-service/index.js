const express = require('express');
const axios = require('axios');
const app = express();
app.use(express.json());

const PORT = 3004;
const ORDER_SERVICE_URL = 'http://localhost:3003';

// Data statis pembayaran
let payments = [
  { id: 1, order_id: 1, amount: 850000, method: 'transfer', status: 'paid', paid_at: '2025-04-01T11:00:00Z' },
  { id: 2, order_id: 2, amount: 15000000, method: 'cash', status: 'paid', paid_at: '2025-04-05T15:00:00Z' },
];
let nextId = 3;

// PROVIDER: GET semua pembayaran
app.get('/payments', (req, res) => {
  res.json({ success: true, data: payments });
});

// PROVIDER: GET pembayaran by ID
app.get('/payments/:id', (req, res) => {
  const payment = payments.find(p => p.id === parseInt(req.params.id));
  if (!payment) return res.status(404).json({ success: false, message: 'Pembayaran tidak ditemukan' });
  res.json({ success: true, data: payment });
});

// CONSUMER + PROVIDER: Buat pembayaran baru
// PaymentService (consumer) ambil data order dari OrderService
// PaymentService (provider) simpan & kembalikan data pembayaran
app.post('/payments', async (req, res) => {
  const { order_id, method } = req.body;

  if (!order_id || !method)
    return res.status(400).json({ success: false, message: 'order_id dan method wajib diisi' });

  try {
    // CONSUMER: ambil data order dari OrderService
    const orderRes = await axios.get(`${ORDER_SERVICE_URL}/orders/${order_id}`);
    const order = orderRes.data.data;

    // Cek apakah order sudah dibayar
    const sudahBayar = payments.find(p => p.order_id === order_id);
    if (sudahBayar)
      return res.status(400).json({ success: false, message: 'Order ini sudah dibayar' });

    // Buat pembayaran baru
    const newPayment = {
      id: nextId++,
      order_id: order.id,
      amount: order.total_price,
      method: method,
      status: 'paid',
      paid_at: new Date().toISOString(),
    };
    payments.push(newPayment);

    // PROVIDER: kembalikan data pembayaran
    res.status(201).json({
      success: true,
      message: 'Pembayaran berhasil',
      data: {
        payment: newPayment,
        order: order,
      },
    });
  } catch (err) {
    const status = err.response?.status || 502;
    const message = err.response?.data?.message || 'Gagal menghubungi OrderService';
    res.status(status).json({ success: false, message, error: err.message });
  }
});

app.listen(PORT, () => console.log(`[PaymentService] berjalan di http://localhost:${PORT}`));