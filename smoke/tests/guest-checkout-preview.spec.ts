import { expect, test } from '@playwright/test';

// Guest checkout flow via API only: list → detail → checkout preview.
// No order created, no payment touched.
test('guest checkout preview flow', async ({ request }) => {
  const list = await request.get('http://localhost:8000/api/v1/products?per_page=1');
  expect(list.ok()).toBeTruthy();
  const products = (await list.json()).data;
  test.skip(!products?.length, 'no products seeded');

  const detail = await request.get(`http://localhost:8000/api/v1/products/${products[0].slug}`);
  expect(detail.ok()).toBeTruthy();
  const variants = (await detail.json()).data?.variants;
  test.skip(!variants?.length, 'no variants');

  const preview = await request.post('http://localhost:8000/api/v1/checkout/preview', {
    data: { items: [{ variant_id: variants[0].id, quantity: 1 }] },
  });
  expect(preview.ok()).toBeTruthy();
  expect((await preview.json()).data?.total).toBeGreaterThan(0);
});
