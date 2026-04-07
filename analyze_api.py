shops = [
    {'name':'HUMFULL @ Toa Payoh','brand':'HUMFULL','status':'OPERATING'},
    {'name':'Drinks Stall Testing Outlet','brand':'Drinks Stall','status':'OPERATING'},
    {'name':'HUMFULL @ Hougang','brand':'HUMFULL','status':'OPERATING'},
    {'name':'JKT Western @ Toa Payoh','brand':'JKT Western','status':'OPERATING'},
    {'name':'JKT Western Testing Outlet','brand':'JKT Western','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Eunos','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'51 Toa Payoh Drinks','brand':'Drinks Stall','status':'OPERATING'},
    {'name':'HUMFULL @ Teck Whye','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL @ Tampines Mart','brand':'HUMFULL','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Punggol','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'Le Le Mee Pok Testing Outlet','brand':'Le Le Mee Pok','status':'OPERATING'},
    {'name':'HUMFULL @ Marsiling','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL @ Jurong East','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL @ Eunos','brand':'HUMFULL','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Teck Whye','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Hougang','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Bukit Batok','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Woodlands Height','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'HUMFULL @ Woodlands Height','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL @ Bukit Batok','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL @ Havelock','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL Testing Outlet','brand':'HUMFULL','status':'OPERATING'},
    {'name':'Le Le Mee Pok @ Toa Payoh','brand':'Le Le Mee Pok','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Taman Jurong','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'HUMFULL @ Taman Jurong','brand':'HUMFULL','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Toa Payoh','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Havelock','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Tampines','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Bedok','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Depot','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OKCR Testing Outlet','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'JKT Western @ Lengkok Bahru','brand':'JKT Western','status':'OPERATING'},
    {'name':'HUMFULL @ Lengkok Bahru','brand':'HUMFULL','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Lengkok Bahru','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Marsiling','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'Telur Thai Lengkok Bahru','brand':'Telur Thai','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ Jurong East','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'HUMFULL @ Yishun','brand':'HUMFULL','status':'OPERATING'},
    {'name':'OK CHICKEN RICE @ AMK','brand':'OK Chicken Rice','status':'CLOSED'},
    {'name':'OK CHICKEN RICE @ Yishun','brand':'OK Chicken Rice','status':'OPERATING'},
    {'name':'HUMFULL @ Edgedale Plains','brand':'HUMFULL','status':'CLOSED'},
    {'name':'Ah Huat @ Tampines Mart','brand':'AH Huat Hokkien Mee','status':'OPERATING'},
    {'name':'HUMFULL @ AMK','brand':'HUMFULL','status':'CLOSED'},
    {'name':'55 Lengkok Bahru Drinks','brand':'Drinks Stall','status':'OPERATING'},
    {'name':'Lele Mee pok @ Lengkok Bahru','brand':'Le Le Mee Pok','status':'OPERATING'},
    {'name':'Ah Huat @ Lengkok Bahru','brand':'AH Huat Hokkien Mee','status':'OPERATING'},
    {'name':'Madas @ Lengkok bahru','brand':'Madas','status':'OPERATING'},
    {'name':'AH HUAT HOKKIEN MEE @ TPY','brand':'AH Huat Hokkien Mee','status':'OPERATING'},
    {'name':'AH HUAT HOKKIEN MEE ( Demo outlet )','brand':'AH Huat Hokkien Mee','status':'OPERATING'},
    {'name':'AH HUAT HOKKIEN MEE @ Bukit Batok','brand':'AH Huat Hokkien Mee','status':'OPERATING'},
    {'name':'HUMFULL @ Punggol','brand':'HUMFULL','status':'OPERATING'},
    {'name':'HUMFULL @ Bedok','brand':'HUMFULL','status':'OPERATING'},
    {'name':'Le Le Mee Pok @ Tampines Mart','brand':'Le Le Mee Pok','status':'OPERATING'},
    {'name':'AH HUAT HOKKIEN MEE @ PUNGGOL','brand':'AH Huat Hokkien Mee','status':'OPERATING'},
    {'name':'Madas Koufu @ Taman Jurong','brand':'Madas','status':'OPERATING'},
]

test_words = ['testing', 'test', 'demo']
closed = [s for s in shops if s['status'] == 'CLOSED']
test_demo = [s for s in shops if any(w in s['name'].lower() for w in test_words)]
real = [s for s in shops if s['status'] != 'CLOSED' and not any(w in s['name'].lower() for w in test_words)]

print(f"TOTAL in API: {len(shops)}")
print(f"Closed ({len(closed)}): {[s['name'] for s in closed]}")
print(f"Test/Demo ({len(test_demo)}): {[s['name'] for s in test_demo]}")
print(f"\nReal operating stalls: {len(real)}")
print()

from collections import Counter
brands = Counter(s['brand'] for s in real)
for b,c in sorted(brands.items(), key=lambda x:-x[1]):
    print(f"  {b}: {c} stalls")
print(f"\nTotal brands: {len(brands)}")
