from BeautifulSoup import BeautifulSoup
import urllib

with open("depts") as f:
	depts = f.readlines()

	for dept in depts:
		url = "http://www.sis.umd.edu/bin/soc?term=201301&crs=" + dept
		with urllib.urlopen(url) as page:
			soup = BeautifulSoup(page)
			listofClasses = soup.find("font","b").find("font")
			