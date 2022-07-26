import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.io.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

public class ExtractLinks {
    private static String PREFIX = "/home/zihuiliu/Downloads/FOXNEWS-20201112T155138Z-001/FOXNEWS/foxnews/";
    public static void main(String args[]) throws Exception{
        Set<String> edges = new HashSet<String>();

        ArrayList<HashMap<String,String>> map = createMap("E:\\IdeaProjects\\ExtractWebLinkJsoup\\FOXNEWS\\URLtoHTML_fox_news.csv");
        HashMap<String,String> fileUrlMap = map.get(0);
        HashMap<String,String> urlFileMap = map.get(1);


        File dir = new File("E:\\IdeaProjects\\ExtractWebLinkJsoup\\FOXNEWS\\foxnews");
        for(File file : dir.listFiles()){
            Document doc = Jsoup.parse(file,"UTF-8",fileUrlMap.get(file.getName()));

            Elements links = doc.select("a[href]");
            Elements media = doc.select("[src]");
            Elements imports = doc.select("link[href]");
            //print("\nMedia: (%d)",media.size());
            /*for (Element src : media){
                if(src.tagName().equals("img"))
                    print(" * %s: <%s> %sx%s (%s)",
                            src.tagName(), src.attr("abs:src"), src.attr("width"), src.attr("height")
                            ,trim(src.attr("alt"),20));
                else
                    print(" * %s: <%s>", src.tagName(), src.attr("abs:src"));
            }*/
           // print("\nImports: (%d)", imports.size());
            /*
            for (Element link : imports){
                print(" * %s <%s> (%s)", link.tagName(), link.attr("abs:href"), link.attr("rel"));
            }*/
            //print("\nLinks: (%d)", links.size());
            for (Element link : links){
                //print(" * a: <%s> (%s)", link.attr("abs:href"), trim(link.text(),35));
                String url = link.attr("abs:href").trim();
                if(urlFileMap.containsKey(url)) {
                    edges.add(PREFIX + file.getName() + " " + PREFIX + urlFileMap.get(url));
                }
            }


        }
        FileWriter writer = new FileWriter("edgeList.txt");
        BufferedWriter out = new BufferedWriter(writer);
        for (String s:edges) {
            out.write(s);
            out.newLine();
        }
        writer.flush();
        writer.close();
    }

    private static void print(String msg, Object... args){
        System.out.println(String.format(msg,args));
    }

    private static String trim(String s, int width) {
        if(s.length() > width)
            return s.substring(0, width-1) + ".";
        else
            return s;
    }

    private static ArrayList<HashMap<String,String>> createMap(String pathToCsv) throws IOException {
        BufferedReader csvReader = new BufferedReader(new FileReader(pathToCsv));
        String row;
        HashMap<String,String> urlFileMap = new HashMap<String,String>();
        HashMap<String,String> fileUrlMap = new HashMap<String,String>();
        while ((row = csvReader.readLine()) != null) {
            String[] data = row.split(",");
            // do something with the data
            fileUrlMap.put(data[0],data[1]);
            urlFileMap.put(data[1],data[0]);
        }
        ArrayList<HashMap<String,String>> map = new ArrayList<HashMap<String,String>>();

        map.add(fileUrlMap);
        map.add(urlFileMap);
        csvReader.close();
        return map;
    }
}
