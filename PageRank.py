# -*- coding: utf-8 -*-
"""
Created on Sat Nov 14 09:29:32 2020

@author: 77378
"""
import networkx as nx
max = 0
G = nx.read_edgelist("edgeList.txt", create_using=nx.DiGraph())
pr = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight',dangling=None)

f = open("external_pageRankFile.txt","w")
for key in pr:
    f.write(str(key)+"="+str(pr[key])+"\n")
    if pr[key] > max:
        max = pr[key]
print("largest: ",max)
f.close()
